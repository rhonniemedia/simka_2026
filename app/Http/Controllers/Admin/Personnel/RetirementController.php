<?php

namespace App\Http\Controllers\Admin\Personnel;

use App\Http\Controllers\Controller;
use App\Models\AsnPositionHistory;
use App\Models\Data;
use App\Models\EmploymentStatus;
use App\Models\StaffStatusHistory;
use App\Services\Personnel\RetirementList;
use Carbon\Carbon;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class RetirementController extends Controller
{
    private const PER_PAGE = 10;

    private const INDEX_VIEW = 'pages.admin.personnel.retirements.index';
    private const TABLE_VIEW = 'pages.admin.personnel.retirements.partials._table';
    private const FORM_MODAL_VIEW = 'pages.admin.personnel.retirements.partials._modal-form';

    /** Status kepegawaian (slug) yang tidak ikut dihitung pensiun. */
    private const EXCLUDED_EMPLOYMENT_SLUGS = ['honorer'];

    /** Status data pegawai yang ditampilkan: aktif, dan yang sudah diproses pensiun. */
    private const LISTED_STATUSES = ['active', 'retired'];

    /*
    |--------------------------------------------------------------------
    | 1. DAFTAR
    |--------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $filters = $this->filtersFrom($request);

        $allRows = $this->buildRows();
        $rows = RetirementList::sort(RetirementList::filter($allRows, $filters));
        $paginator = $this->paginate($rows, $request);

        if ($request->header('HX-Request')) {
            return view(self::TABLE_VIEW, ['rows' => $paginator]);
        }

        return view(self::INDEX_VIEW, [
            'rows' => $paginator,
            'search' => $filters['search'],
            'filterRetirementStatus' => $filters['retirement_status'],
            'filterEmploymentStatus' => $filters['employment_status'],
            'filterPositionType' => $filters['position_type'],
            'filterYear' => $filters['year'],
            'filterGender' => $filters['gender'],
            'retirementStatusOptions' => $this->retirementStatusOptions(),
            'employmentOptions' => $this->employmentOptions(),
            'positionTypeOptions' => $this->positionTypeOptions(),
            'yearOptions' => RetirementList::yearOptions($allRows),
        ]);
    }

    /*
    |--------------------------------------------------------------------
    | 2. MODAL PROSES
    |--------------------------------------------------------------------
    */

    public function process(string $id)
    {
        $row = $this->buildRows($id)[0] ?? null;

        if ($row === null) {
            abort(404);
        }

        if (! $row['calc']['can_process']) {
            return $this->notProcessableResponse($row);
        }

        return view(self::FORM_MODAL_VIEW, ['row' => $row]);
    }

    /*
    |--------------------------------------------------------------------
    | 3. SIMPAN
    |--------------------------------------------------------------------
    | Sama seperti fitur Mutasi: perubahan status ditulis ke riwayat
    | (staff_status_histories) dan ke data pegawai dalam satu transaksi.
    |
    | Status & TMT tidak diambil dari input: keduanya dihitung ulang di
    | server. Baris pegawai dikunci agar tidak diproses dua kali.
    */

    public function store(Request $request, string $id)
    {
        try {
            $validated = $request->validate(
                [
                    'decree_number' => ['required', 'string', 'max:255'],
                    'note' => ['nullable', 'string', 'max:1000'],
                ],
                [
                    'required' => ':attribute wajib diisi.',
                    'string' => ':attribute harus berupa teks.',
                    'max' => ':attribute maksimal :max karakter.',
                ],
                [
                    'decree_number' => 'Nomor SK',
                    'note' => 'Catatan',
                ]
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        }

        $outcome = DB::transaction(function () use ($id, $validated) {
            Data::completed()->whereKey($id)->lockForUpdate()->firstOrFail();

            $row = $this->buildRows($id)[0] ?? null;

            if ($row === null) {
                return ['ok' => false, 'message' => 'Data pegawai tidak ditemukan atau tidak termasuk daftar pensiun.'];
            }

            if (! $row['calc']['can_process']) {
                return ['ok' => false, 'message' => $this->notProcessableMessage($row)];
            }

            $staff = $row['staff'];
            $tmt = $row['calc']['tmt'];

            StaffStatusHistory::create([
                'staff_id' => $staff->id,
                'from_status' => $staff->status,
                'to_status' => RetirementList::RETIRED_STATUS,
                'decree_number' => $validated['decree_number'],
                'effective_date' => $tmt->format('Y-m-d'),
                'note' => $validated['note'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $staff->update([
                'status' => RetirementList::RETIRED_STATUS,
                'status_effective_date' => $tmt->format('Y-m-d'),
                'updated_by' => Auth::id(),
            ]);

            return ['ok' => true, 'name' => $staff->name, 'tmt' => $tmt];
        });

        if (! $outcome['ok']) {
            return $this->alertResponse($request, 'warning', 'Tidak dapat diproses', $outcome['message'], 422, true);
        }

        return $this->alertResponse(
            $request,
            'success',
            'Berhasil!',
            $outcome['name'] . ' berhasil diproses pensiun, TMT ' . $this->formatDate($outcome['tmt']) . '.',
            200,
            true
        );
    }

    /*
    |--------------------------------------------------------------------
    | Penyusunan data
    |--------------------------------------------------------------------
    */

    /**
     * Semua pegawai yang masuk daftar pensiun (kecuali honorer) beserta
     * hasil perhitungannya. Perhitungan dilakukan di PHP karena tanggal
     * lahir terenkripsi.
     *
     * @return array<int, array>
     */
    private function buildRows(?string $onlyStaffId = null): array
    {
        $query = Data::completed()
            ->whereIn('staff_data.status', self::LISTED_STATUSES)
            ->whereHas('employmentStatus', fn($q) => $q->whereNotIn('slug', self::EXCLUDED_EMPLOYMENT_SLUGS))
            ->with(['vault', 'employmentStatus']);

        if ($onlyStaffId !== null) {
            $query->whereKey($onlyStaffId);
        }

        $staffList = $query->get();

        if ($staffList->isEmpty()) {
            return [];
        }

        // Jabatan ASN aktif; bila ada lebih dari satu, ambil yang TMT-nya terbaru.
        $activePositions = AsnPositionHistory::with('asnPosition')
            ->whereIn('staff_id', $staffList->pluck('id'))
            ->where('is_active', true)
            ->orderByDesc('effective_date')
            ->get()
            ->unique('staff_id')
            ->keyBy('staff_id');

        $today = new DateTimeImmutable('today');
        $rows = [];

        foreach ($staffList as $staff) {
            $rows[] = RetirementList::makeRow(
                $staff,
                $activePositions->get($staff->id)?->asnPosition,
                $this->readDob($staff),
                $today
            );
        }

        return $rows;
    }

    private function readDob(Data $staff): ?string
    {
        try {
            return $staff->vault?->dob;
        } catch (Throwable) {
            // Data terenkripsi rusak / kunci berbeda: perlakukan sebagai tidak diketahui.
            return null;
        }
    }

    private function filtersFrom(Request $request): array
    {
        $get = function (string $key) use ($request): string {
            $value = $request->input($key, '');

            return is_scalar($value) ? trim((string) $value) : '';
        };

        return [
            'search' => $get('search'),
            'retirement_status' => $get('filter_retirement_status'),
            'employment_status' => $get('filter_employment_status'),
            'position_type' => $get('filter_position_type'),
            'year' => $get('filter_year'),
            'gender' => $get('filter_gender'),
        ];
    }

    private function paginate(array $rows, Request $request): LengthAwarePaginator
    {
        $total = count($rows);
        $lastPage = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, LengthAwarePaginator::resolveCurrentPage()), $lastPage);

        return (new LengthAwarePaginator(
            array_slice($rows, ($page - 1) * self::PER_PAGE, self::PER_PAGE),
            $total,
            self::PER_PAGE,
            $page,
            ['path' => $request->url()]
        ))->withQueryString();
    }

    /*
    |--------------------------------------------------------------------
    | Opsi filter
    |--------------------------------------------------------------------
    */

    private function retirementStatusOptions(): array
    {
        return [
            ['value' => 'reached', 'label' => 'Sudah mencapai batas'],
            ['value' => 'soon', 'label' => 'Segera pensiun'],
            ['value' => 'not_yet', 'label' => 'Belum pensiun'],
            ['value' => 'retired', 'label' => 'Sudah pensiun'],
            ['value' => 'unknown', 'label' => 'Tanggal lahir belum valid'],
        ];
    }

    private function positionTypeOptions(): array
    {
        return [
            ['value' => 'fungsional_keahlian', 'label' => 'Fungsional Keahlian'],
            ['value' => 'fungsional_keterampilan', 'label' => 'Fungsional Keterampilan'],
            ['value' => 'pelaksana', 'label' => 'Pelaksana'],
        ];
    }

    private function employmentOptions(): array
    {
        return EmploymentStatus::whereNotIn('slug', self::EXCLUDED_EMPLOYMENT_SLUGS)
            ->orderBy('code')
            ->get(['id', 'name'])
            ->map(fn($e) => ['value' => (string) $e->id, 'label' => $e->name])
            ->all();
    }

    /*
    |--------------------------------------------------------------------
    | Respons
    |--------------------------------------------------------------------
    */

    private function notProcessableMessage(array $row): string
    {
        return $row['calc']['state'] === 'retired'
            ? 'Pegawai ini sudah diproses pensiun.'
            : 'Pegawai ini belum mencapai batas usia pensiun.';
    }

    /**
     * Modal tidak dibuka (tombol basi / diakses langsung): tampilkan pemberitahuan
     * dan segarkan tabel agar barisnya mengikuti kondisi terbaru.
     */
    private function notProcessableResponse(array $row)
    {
        return response('', 204)->header('HX-Trigger', json_encode([
            'showAlert' => [
                'icon' => 'warning',
                'title' => 'Tidak dapat diproses',
                'text' => $this->notProcessableMessage($row),
            ],
            'refreshRetirementData' => true,
        ]));
    }

    private function alertResponse(Request $request, string $icon, string $title, string $text, int $status, bool $closeModal = false)
    {
        if ($request->header('HX-Request')) {
            $triggers = [
                'showAlert' => ['icon' => $icon, 'title' => $title, 'text' => $text],
                'refreshRetirementData' => true,
            ];

            if ($closeModal) {
                $triggers = ['close-modal' => true] + $triggers;
            }

            return response('', $status)->header('HX-Trigger', json_encode($triggers));
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => $status < 400, 'message' => $text], $status);
        }

        return redirect()->route('admin.personnel.retirement.index');
    }

    /**
     * Format sama dengan modul Mutasi: JSON { message, errors } agar modal
     * dapat menampilkan pesan di bawah masing-masing field.
     */
    private function validationErrorResponse(ValidationException $e): JsonResponse
    {
        return response()->json([
            'message' => $e->getMessage(),
            'errors' => $e->errors(),
        ], $e->status);
    }

    private function formatDate(DateTimeImmutable $date): string
    {
        return Carbon::parse($date->format('Y-m-d'))->locale('id')->isoFormat('D MMMM Y');
    }
}
