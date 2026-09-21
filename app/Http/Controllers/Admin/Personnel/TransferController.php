<?php

namespace App\Http\Controllers\Admin\Personnel;

use App\Http\Controllers\Controller;
use App\Models\Data;
use App\Models\EmploymentStatus;
use App\Models\StaffStatusHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TransferController extends Controller
{
    private const TABLE_VIEW = 'pages.admin.personnel.transfers.partials._table';
    private const FORM_VIEW = 'pages.admin.personnel.transfers.partials._form-modal';

    /** Semua status "keluar" (selain aktif) yang tampil di daftar ini. Pensiun tetap ikut ditampilkan (read-only), meski perubahannya lewat menu Pensiun sendiri. */
    private const OUT_STATUSES = ['transferred', 'resigned', 'retired', 'deceased', 'dismissed'];

    /** Pilihan status tujuan pada tombol "Mutasi". Pensiun sengaja tidak dimasukkan (menu terpisah). */
    private const MUTATION_TARGET_STATUSES = ['transferred', 'resigned', 'deceased', 'dismissed'];

    /** Status yang boleh diaktifkan kembali lewat tombol "Reaktivasi". */
    private const REACTIVATABLE_STATUSES = ['transferred', 'resigned'];

    /** Status kepegawaian (slug) yang muncul di filter: PNS, PPPK, dan PPPK Paruh Waktu. */
    private const INCLUDED_EMPLOYMENT_SLUGS = ['pns', 'pppk', 'pppkpw'];

    public const STATUS_LABELS = [
        'active' => 'Aktif',
        'transferred' => 'Pindah',
        'resigned' => 'Mengundurkan Diri',
        'retired' => 'Pensiun',
        'deceased' => 'Meninggal Dunia',
        'dismissed' => 'Diberhentikan',
    ];

    /*
    |--------------------------------------------------------------------
    | Daftar
    |--------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $year = $request->input('year');
        $filterStatus = $request->input('filter_status');
        $filterEmploymentStatus = $request->input('filter_employment_status');

        $query = Data::completed()
            ->with(['vault'])
            ->whereIn('status', self::OUT_STATUSES)
            ->addSelect([
                'latest_decree_number' => StaffStatusHistory::select('decree_number')
                    ->whereColumn('staff_id', 'staff_data.id')
                    ->whereColumn('to_status', 'staff_data.status')
                    ->orderByDesc('effective_date')
                    ->orderByDesc('created_at')
                    ->limit(1),
                'latest_note' => StaffStatusHistory::select('note')
                    ->whereColumn('staff_id', 'staff_data.id')
                    ->whereColumn('to_status', 'staff_data.status')
                    ->orderByDesc('effective_date')
                    ->orderByDesc('created_at')
                    ->limit(1),
            ]);

        if ($year === 'all') {
            // Tanpa batasan tahun sama sekali.
        } elseif (!empty($year)) {
            $query->whereYear('status_effective_date', $year);
        } else {
            // Default: 1 tahun terakhir dari sekarang.
            $query->where('status_effective_date', '>=', now()->subYear()->startOfDay());
        }

        if (!empty($filterStatus)) {
            $query->where('status', $filterStatus);
        }

        if (!empty($filterEmploymentStatus)) {
            $query->where('employment_id', $filterEmploymentStatus);
        }

        $staffList = $query->orderByDesc('status_effective_date')->paginate(10)->withQueryString();

        if ($request->header('HX-Request')) {
            return view(self::TABLE_VIEW, compact('staffList'));
        }

        // Daftar tahun untuk dropdown filter: dari tahun status_effective_date
        // tertua yang tercatat (kelompok status "keluar") sampai tahun ini.
        $earliestYear = Data::completed()
            ->whereIn('status', self::OUT_STATUSES)
            ->whereNotNull('status_effective_date')
            ->min(DB::raw('YEAR(status_effective_date)'));

        $yearOptions = array_merge(
            ['all'],
            range((int) now()->year, (int) ($earliestYear ?: now()->year))
        );

        $employmentOptions = EmploymentStatus::whereIn('slug', self::INCLUDED_EMPLOYMENT_SLUGS)
            ->orderBy('code')
            ->get(['id', 'name']);

        return view('pages.admin.personnel.transfers.index', compact(
            'staffList',
            'year',
            'filterStatus',
            'filterEmploymentStatus',
            'yearOptions',
            'employmentOptions'
        ));
    }

    /*
    |--------------------------------------------------------------------
    | Form: Mutasi (aktif -> keluar) & Reaktivasi (keluar -> aktif)
    |--------------------------------------------------------------------
    */

    public function create()
    {
        $staffOptions = Data::completed()
            ->where('status', 'active')
            ->orderBy('name')
            ->pluck('name', 'id');

        return view(self::FORM_VIEW, [
            'mode' => 'mutasi',
            'staffOptions' => $staffOptions,
            'statusOptions' => collect(self::MUTATION_TARGET_STATUSES)
                ->mapWithKeys(fn($s) => [$s => self::STATUS_LABELS[$s]]),
        ]);
    }

    public function createReactivation()
    {
        $staffOptions = Data::completed()
            ->whereIn('status', self::REACTIVATABLE_STATUSES)
            ->orderBy('name')
            ->pluck('name', 'id');

        return view(self::FORM_VIEW, [
            'mode' => 'reaktivasi',
            'staffOptions' => $staffOptions,
            'statusOptions' => collect(),
        ]);
    }

    /*
    |--------------------------------------------------------------------
    | Simpan
    |--------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $mode = $request->input('mode');

        if ($mode === 'reaktivasi') {
            return $this->storeReactivation($request);
        }

        return $this->storeMutation($request);
    }

    private function storeMutation(Request $request)
    {
        try {
            $validated = $request->validate(
                [
                    'staff_id' => [
                        'required',
                        'uuid',
                        Rule::exists('staff_data', 'id')->where('status', 'active'),
                    ],
                    'to_status' => ['required', Rule::in(self::MUTATION_TARGET_STATUSES)],
                    'decree_number' => ['required', 'string', 'max:255'],
                    'effective_date' => ['required', 'date'],
                    'note' => ['nullable', 'string', 'max:1000'],
                ],
                [
                    'staff_id.exists' => 'Pegawai yang dipilih tidak valid atau sudah tidak berstatus aktif.',
                    'required' => ':attribute wajib diisi.',
                    'date' => ':attribute harus berupa tanggal yang valid.',
                ],
                [
                    'staff_id' => 'Pegawai',
                    'to_status' => 'Status tujuan',
                    'decree_number' => 'Nomor SK',
                    'effective_date' => 'TMT',
                ]
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        }

        $staff = Data::completed()->findOrFail($validated['staff_id']);

        DB::transaction(function () use ($staff, $validated) {
            StaffStatusHistory::create([
                'staff_id' => $staff->id,
                'from_status' => $staff->status,
                'to_status' => $validated['to_status'],
                'decree_number' => $validated['decree_number'],
                'effective_date' => $validated['effective_date'],
                'note' => $validated['note'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $staff->status = $validated['to_status'];
            $staff->status_effective_date = $validated['effective_date'];
            $staff->updated_by = Auth::id();
            $staff->save();
        });

        return $this->respondWithSuccess($request, 'Status pegawai berhasil diperbarui.');
    }

    private function storeReactivation(Request $request)
    {
        try {
            $validated = $request->validate(
                [
                    'staff_id' => [
                        'required',
                        'uuid',
                        Rule::exists('staff_data', 'id')->where(
                            fn($q) => $q->whereIn('status', self::REACTIVATABLE_STATUSES)
                        ),
                    ],
                    'decree_number' => ['required', 'string', 'max:255'],
                    'effective_date' => ['required', 'date'],
                    'note' => ['nullable', 'string', 'max:1000'],
                ],
                [
                    'staff_id.exists' => 'Pegawai yang dipilih tidak valid atau tidak berstatus Pindah/Mengundurkan Diri.',
                    'required' => ':attribute wajib diisi.',
                    'date' => ':attribute harus berupa tanggal yang valid.',
                ],
                [
                    'staff_id' => 'Pegawai',
                    'decree_number' => 'Nomor SK',
                    'effective_date' => 'TMT',
                ]
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        }

        $staff = Data::completed()->findOrFail($validated['staff_id']);

        DB::transaction(function () use ($staff, $validated) {
            StaffStatusHistory::create([
                'staff_id' => $staff->id,
                'from_status' => $staff->status,
                'to_status' => 'active',
                'decree_number' => $validated['decree_number'],
                'effective_date' => $validated['effective_date'],
                'note' => $validated['note'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $staff->status = 'active';
            $staff->status_effective_date = $validated['effective_date'];
            $staff->updated_by = Auth::id();
            $staff->save();
        });

        return $this->respondWithSuccess($request, 'Pegawai berhasil diaktifkan kembali.');
    }

    /*
    |--------------------------------------------------------------------
    | Respons
    |--------------------------------------------------------------------
    */

    private function respondWithSuccess(Request $request, string $message)
    {
        if ($request->header('HX-Request')) {
            $table = $this->index($request)->render();

            return response($table)
                ->header('HX-Trigger', json_encode([
                    'close-modal' => true,
                    'showAlert' => [
                        'icon' => 'success',
                        'title' => 'Berhasil!',
                        'text' => $message,
                    ],
                ]));
        }

        return redirect()->route('admin.personnel.mutation.index');
    }

    private function validationErrorResponse(ValidationException $e): JsonResponse
    {
        return response()->json([
            'message' => $e->getMessage(),
            'errors' => $e->errors(),
        ], $e->status);
    }
}
