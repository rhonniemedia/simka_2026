<?php

namespace App\Http\Controllers\Admin\Career;

use App\Http\Controllers\Controller;
use App\Models\AsnPositionHistory;
use App\Models\Data;
use App\Models\EmploymentStatus;
use App\Models\StaffStatusHistory;
use App\Services\PdfService;
use App\Services\Personnel\RetirementList;
use App\Services\SchoolProfile;
use App\Services\Staff\StaffDocumentService;
use App\Support\TableSort;
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
    /** Kode kategori berkas SK pensiun (tabel staff_document_categories). */
    private const SK_CATEGORY = 'sk_pensiun';

    private const PER_PAGE = 10;

    private const INDEX_VIEW = 'pages.admin.career.retirements.index';
    private const TABLE_VIEW = 'pages.admin.career.retirements.partials._table';
    private const FORM_MODAL_VIEW = 'pages.admin.career.retirements.partials._modal-form';

    /** Status kepegawaian (slug) yang tidak ikut dihitung pensiun. */
    private const EXCLUDED_EMPLOYMENT_SLUGS = ['honorer'];

    /** Status data pegawai yang ditampilkan: aktif, dan yang sudah diproses pensiun. */
    private const LISTED_STATUSES = ['active', 'retired'];

    /** Urutan status saat disortir naik: yang paling mendesak lebih dulu. */
    private const STATE_RANK = [
        'reached' => 0,
        'soon'    => 1,
        'not_yet' => 2,
        'unknown' => 3,
        'retired' => 4,
    ];

    /*
    |--------------------------------------------------------------------
    | 1. DAFTAR
    |--------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $filters = $this->filtersFrom($request);

        // Tanpa default: urutan bawaan RetirementList dipakai sampai header diklik
        $sorter = TableSort::make($request, [
            'name'   => null,
            'tmt'    => null,
            'status' => null,
        ], default: null, defaultDirection: 'asc');

        $allRows = $this->buildRows();
        $rows = RetirementList::sort(RetirementList::filter($allRows, $filters));

        // Daftar disusun di PHP (tanggal lahir terenkripsi), jadi sortir juga di array.
        // Nilai null (mis. TMT kosong) selalu di akhir.
        $rows = $sorter->sortArray($rows, [
            'name'   => fn($r) => $r['staff']->name,
            'tmt'    => fn($r) => ($r['tmt'] ?? ($r['calc']['tmt'] ?? null))?->getTimestamp(),
            'status' => fn($r) => self::STATE_RANK[$r['calc']['state'] ?? 'unknown'] ?? 99,
            'id'     => fn($r) => (string) $r['staff']->id,
        ], then: $sorter->key() === 'status'
            ? ['tmt', 'name', 'id']
            : ['name', 'id']);

        $paginator = $this->paginate($rows, $request);

        // Berkas SK pensiun untuk pegawai yang sudah pensiun di halaman ini
        $this->attachRetirementDocuments($paginator);

        if ($request->header('HX-Request')) {
            return view(self::TABLE_VIEW, ['rows' => $paginator] + $sorter->viewData());
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
        ] + $sorter->viewData());
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
    */

    public function store(Request $request, string $id)
    {
        try {
            $validated = $this->validatedDecree($request);

            $outcome = DB::transaction(function () use ($request, $id, $validated) {
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

                $history = StaffStatusHistory::create([
                    'staff_id' => $staff->id,
                    'from_status' => $staff->status,
                    'to_status' => RetirementList::RETIRED_STATUS,
                    'decree_number' => $validated['decree_number'] ?? null,
                    'decree_date' => $validated['decree_date'] ?? null,
                    'effective_date' => $tmt->format('Y-m-d'),
                    'note' => $validated['note'] ?? null,
                    'created_by' => Auth::id(),
                ]);

                if ($request->hasFile('file')) {
                    $history->attachDocument($request->file('file'), self::SK_CATEGORY);
                }

                $staff->update(['updated_by' => Auth::id()]);

                return ['ok' => true, 'name' => $staff->name, 'tmt' => $tmt];
            });
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        }

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
    | 4. MODAL EDIT
    |--------------------------------------------------------------------
    */

    public function edit(string $id)
    {
        $row = $this->buildRows($id)[0] ?? null;

        if ($row === null) {
            abort(404);
        }

        $history = $row['calc']['state'] === 'retired' ? $this->latestRetirementHistory($id) : null;

        if ($history === null) {
            return $this->notEditableResponse();
        }

        return view(self::FORM_MODAL_VIEW, [
            'row' => $row,
            'history' => $history,
        ]);
    }

    public function update(Request $request, string $id)
    {
        try {
            $validated = $this->validatedDecree($request);

            $outcome = DB::transaction(function () use ($request, $id, $validated) {
                Data::completed()->whereKey($id)->lockForUpdate()->firstOrFail();

                $row = $this->buildRows($id)[0] ?? null;
                $history = ($row !== null && $row['calc']['state'] === 'retired')
                    ? $this->latestRetirementHistory($id)
                    : null;

                if ($history === null) {
                    return ['ok' => false, 'message' => 'Data pensiun pegawai ini tidak ditemukan.'];
                }

                $history->update([
                    'decree_number' => $validated['decree_number'] ?? null,
                    'decree_date' => $validated['decree_date'] ?? null,
                    'note' => $validated['note'] ?? null,
                ]);

                if ($request->hasFile('file')) {
                    $oldDocuments = $history->documents
                        ->filter(fn($d) => $d->category?->code === self::SK_CATEGORY);

                    $history->attachDocument($request->file('file'), self::SK_CATEGORY);

                    $oldDocuments->each->delete();
                }

                return ['ok' => true, 'name' => $row['staff']->name];
            });
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        }

        if (! $outcome['ok']) {
            return $this->alertResponse($request, 'warning', 'Tidak dapat diubah', $outcome['message'], 422, true);
        }

        return $this->alertResponse(
            $request,
            'success',
            'Berhasil!',
            'Data pensiun ' . $outcome['name'] . ' berhasil diperbarui.',
            200,
            true
        );
    }

    /*
|--------------------------------------------------------------------
| Cetak PDF
|--------------------------------------------------------------------
*/

    public function generatePdf(Request $request, PdfService $pdf)
    {
        // Periode: 'one_year' = akan pensiun dalam 1 tahun ke depan, selain itu = semua
        $period = $request->input('period') === 'one_year' ? 'one_year' : 'all';

        $statusInput = $request->input('status');
        $status = is_string($statusInput)
            && in_array($statusInput, array_column($this->retirementStatusOptions(), 'value'), true)
            ? $statusInput
            : '';

        // Jalur yang sama dengan halaman daftar (tanpa paginasi), jadi hasilnya konsisten.
        $rows = RetirementList::sort(
            RetirementList::filter($this->buildRows(), ['retirement_status' => $status])
        );

        if ($period === 'one_year') {
            $limit = new DateTimeImmutable('today +1 year');

            // Belum diproses pensiun, TMT-nya valid, dan jatuh paling lambat 1 tahun dari hari ini
            // (yang sudah melewati batas tetapi belum diproses ikut masuk).
            $rows = array_values(array_filter(
                $rows,
                fn(array $r) => $r['calc']['state'] !== 'retired'
                    && $r['tmt'] !== null
                    && $r['tmt'] <= $limit
            ));
        }

        $stateLabels = array_column($this->retirementStatusOptions(), 'label', 'value');

        $data = array_map(function (array $r) use ($stateLabels) {
            $staff = $r['staff'];
            $calc  = $r['calc'];

            $nip = $this->readVault($staff, 'nip');

            return [
                'name'        => $staff->name,
                'nip'         => (! empty($nip) && strcasecmp($nip, 'tidak ada') !== 0) ? $nip : '-',
                'dob'         => $this->pdfDate($calc['dob'] ?? null),
                'age'         => ($calc['age'] ?? null) !== null ? $calc['age'] . ' tahun' : null,
                'employment'  => $staff->employmentStatus->alias ?? $staff->employmentStatus?->name ?? '-',
                'position'    => $r['position']?->name ?? '-',
                'phone'       => $this->readVault($staff, 'phone_number') ?: '-',
                'tmt'         => $this->pdfDate($r['tmt']),
                'limit_age'   => $calc['limit_age'] ?? null,
                'status'      => $stateLabels[$calc['state']] ?? '-',
                'note'        => match ($calc['state']) {
                    'reached' => ! empty($calc['limit_date']) ? 'Sejak ' . $this->pdfDate($calc['limit_date']) : null,
                    'soon'    => '± ' . $calc['months_left'] . ' bulan lagi',
                    default   => null,
                },
            ];
        }, $rows);

        return $pdf->output(
            'pages.admin.pdf.retirement',
            [
                'title'       => 'Daftar Pensiun Pegawai',
                'periode'     => $period === 'one_year' ? '1 Tahun Sebelum Pensiun' : 'Semua',
                'statusLabel' => $status !== '' ? ($stateLabels[$status] ?? null) : null,
                'printedAt'   => $this->pdfDate(new DateTimeImmutable('today')),
                'school'      => SchoolProfile::get()['name'],
                'rows'        => $data,
            ],
            'pensiun-' . ($period === 'one_year' ? '1-tahun' : 'semua') . '-' . now()->format('Ymd') . '.pdf',
            $request->boolean('download'),
            'a4',
            'landscape'
        );
    }

    /** Baca atribut terenkripsi di vault; null bila kosong atau gagal didekripsi. */
    private function readVault(Data $staff, string $attribute): ?string
    {
        try {
            return $staff->vault?->{$attribute};
        } catch (Throwable) {
            return null;
        }
    }

    /** Tanggal Indonesia, mis. "1 Januari 2026" (null -> '-'). */
    private function pdfDate(?\DateTimeInterface $date): string
    {
        return $date
            ? Carbon::parse($date->format('Y-m-d'))->locale('id')->translatedFormat('j F Y')
            : '-';
    }

    private function latestRetirementHistory(string $staffId): ?StaffStatusHistory
    {
        return StaffStatusHistory::with('documents.category')
            ->where('staff_id', $staffId)
            ->where('to_status', RetirementList::RETIRED_STATUS)
            ->orderByDesc('effective_date')
            ->orderByDesc('created_at')
            ->first();
    }

    /** Aturan validasi SK yang sama untuk proses (create) dan edit. */
    private function validatedDecree(Request $request): array
    {
        return $request->validate(
            [
                'decree_number' => ['nullable', 'string', 'max:255'],
                'decree_date' => ['nullable', 'date'],
                'note' => ['nullable', 'string', 'max:1000'],
                'file' => ['nullable', 'file', 'mimes:pdf', 'max:' . StaffDocumentService::MAX_SIZE_KB],
            ],
            [
                'string' => ':attribute harus berupa teks.',
                'max' => ':attribute maksimal :max karakter.',
                'date' => ':attribute harus berupa tanggal yang valid.',
                'file.mimes' => 'Berkas SK harus berformat PDF.',
                'file.max' => 'Ukuran berkas SK maksimal 1 MB.',
                'file.uploaded' => 'Berkas SK gagal diunggah. Pastikan ukurannya maksimal 1 MB.',
            ],
            [
                'decree_number' => 'Nomor SK',
                'decree_date' => 'Tanggal SK',
                'note' => 'Catatan',
                'file' => 'Berkas SK',
            ]
        );
    }

    /**
     * Tempelkan berkas SK dari peristiwa pensiun terakhir tiap pegawai
     * (status 'retired') sebagai relasi 'retirementDocuments' pada model staff.
     */
    private function attachRetirementDocuments(LengthAwarePaginator $paginator): void
    {
        $staffIds = collect($paginator->items())->pluck('staff.id')->all();

        $histories = StaffStatusHistory::with('documents.category')
            ->whereIn('staff_id', $staffIds)
            ->where('to_status', RetirementList::RETIRED_STATUS)
            ->orderByDesc('effective_date')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('staff_id');

        foreach ($paginator->items() as $row) {
            $row['staff']->setRelation(
                'retirementDocuments',
                $histories->get($row['staff']->id)?->first()?->documents ?? collect()
            );
        }
    }

    /*
    |--------------------------------------------------------------------
    | Penyusunan data
    |--------------------------------------------------------------------
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

    private function notEditableResponse()
    {
        return response('', 204)->header('HX-Trigger', json_encode([
            'showAlert' => [
                'icon' => 'warning',
                'title' => 'Tidak dapat diubah',
                'text' => 'Pegawai ini belum diproses pensiun.',
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

    private function validationErrorResponse(ValidationException $e): JsonResponse
    {
        $known = ['decree_number', 'decree_date', 'note', 'file'];
        $errors = [];

        foreach ($e->errors() as $field => $messages) {
            $key = in_array($field, $known, true) ? $field : 'file';
            $errors[$key] = array_merge($errors[$key] ?? [], $messages);
        }

        return response()->json([
            'message' => $e->getMessage(),
            'errors' => $errors,
        ], $e->status);
    }

    private function formatDate(DateTimeImmutable $date): string
    {
        return Carbon::parse($date->format('Y-m-d'))->locale('id')->isoFormat('D MMMM Y');
    }
}
