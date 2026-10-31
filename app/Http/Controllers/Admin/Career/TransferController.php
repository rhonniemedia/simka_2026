<?php

namespace App\Http\Controllers\Admin\Career;

use App\Http\Controllers\Controller;
use App\Models\Data;
use App\Models\EmploymentStatus;
use App\Models\StaffStatusHistory;
use App\Services\Staff\StaffDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TransferController extends Controller
{
    private const TABLE_VIEW = 'pages.admin.career.transfers.partials._table';
    private const FORM_VIEW = 'pages.admin.career.transfers.partials._form-modal';

    /** Semua status "keluar" (selain aktif) yang tampil di daftar ini. Pensiun tetap ikut ditampilkan (read-only), meski perubahannya lewat menu Pensiun sendiri. */
    private const OUT_STATUSES = ['transferred', 'resigned', 'retired', 'deceased', 'dismissed'];

    /** Pilihan status tujuan pada tombol "Mutasi". Pensiun sengaja tidak dimasukkan (menu terpisah). */
    private const MUTATION_TARGET_STATUSES = ['transferred', 'resigned', 'deceased', 'dismissed'];

    /** Status yang boleh diaktifkan kembali lewat tombol "Reaktivasi". */
    private const REACTIVATABLE_STATUSES = ['transferred', 'resigned'];

    /** Status kepegawaian (slug) yang muncul di filter: PNS, PPPK, dan PPPK Paruh Waktu. */
    private const INCLUDED_EMPLOYMENT_SLUGS = ['pns', 'pppk', 'pppkpw'];

    /**
     * Kode kategori berkas SK menurut status tujuan (kolom `code` di
     * staff_document_categories). 'active' = alur Reaktivasi.
     * Pensiun (retired) ditangani menu Pensiun.
     */
    private const SK_CATEGORY = [
        'transferred' => 'sk_mutasi',
        'resigned'    => 'sk_pengunduran_diri',
        'dismissed'   => 'sk_pemberhentian',
        'deceased'    => 'surat_kematian',
        'active'      => 'sk_reaktivasi',
    ];

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

        // Berkas SK dari peristiwa status terakhir tiap pegawai di halaman ini
        $this->attachLatestDocuments($staffList);

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

        return view('pages.admin.career.transfers.index', compact(
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
                $this->rules([
                    'staff_id' => [
                        'required',
                        'uuid',
                        Rule::exists('staff_data', 'id')->where('status', 'active'),
                    ],
                    'to_status' => ['required', Rule::in(self::MUTATION_TARGET_STATUSES)],
                ]),
                $this->messages([
                    'staff_id.exists' => 'Pegawai yang dipilih tidak valid atau sudah tidak berstatus aktif.',
                ]),
                $this->attributes()
            );

            $staff = Data::completed()->findOrFail($validated['staff_id']);

            DB::transaction(fn() => $this->recordStatusChange($staff, $validated['to_status'], $validated, $request));
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        }

        return $this->respondWithSuccess($request, 'Status pegawai berhasil diperbarui.');
    }

    private function storeReactivation(Request $request)
    {
        try {
            $validated = $request->validate(
                $this->rules([
                    'staff_id' => [
                        'required',
                        'uuid',
                        Rule::exists('staff_data', 'id')->where(
                            fn($q) => $q->whereIn('status', self::REACTIVATABLE_STATUSES)
                        ),
                    ],
                ]),
                $this->messages([
                    'staff_id.exists' => 'Pegawai yang dipilih tidak valid atau tidak berstatus Pindah/Mengundurkan Diri.',
                ]),
                $this->attributes()
            );

            $staff = Data::completed()->findOrFail($validated['staff_id']);

            DB::transaction(fn() => $this->recordStatusChange($staff, 'active', $validated, $request));
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        }

        return $this->respondWithSuccess($request, 'Pegawai berhasil diaktifkan kembali.');
    }

    /*
    |--------------------------------------------------------------------
    | Edit data SK (status terakhir pegawai)
    |--------------------------------------------------------------------
    | Yang dapat diubah: nomor SK, tanggal SK, TMT, catatan, dan berkas.
    | Pegawai dan status tujuan tetap. Pensiun diubah lewat menu Pensiun.
    */

    public function edit(string $id)
    {
        $staff = Data::completed()->findOrFail($id);
        $history = $this->editableHistory($staff);

        if ($history === null) {
            return $this->notEditableResponse();
        }

        return view(self::FORM_VIEW, [
            'mode' => 'edit',
            'staff' => $staff,
            'history' => $history,
            'staffOptions' => collect(),
            'statusOptions' => collect(),
        ]);
    }

    public function update(Request $request, string $id)
    {
        try {
            $validated = $request->validate(
                $this->rules([]),
                $this->messages(),
                $this->attributes()
            );

            $outcome = DB::transaction(function () use ($request, $id, $validated) {
                $staff = Data::completed()->whereKey($id)->lockForUpdate()->firstOrFail();
                $history = $this->editableHistory($staff);

                if ($history === null) {
                    return false;
                }

                $history->update([
                    'decree_number'  => $validated['decree_number'],
                    'decree_date'    => $validated['decree_date'],
                    'effective_date' => $validated['effective_date'],
                    'note'           => $validated['note'] ?? null,
                ]);

                // Berkas baru menggantikan SK lama pada peristiwa status ini.
                if ($request->hasFile('file')) {
                    $oldDocuments = $history->documents;

                    $history->attachDocument($request->file('file'), self::SK_CATEGORY[$history->to_status]);

                    $oldDocuments->each->delete();
                }

                // TMT di data pegawai mengikuti TMT riwayat terakhir.
                $staff->forceFill([
                    'status_effective_date' => $validated['effective_date'],
                    'updated_by' => Auth::id(),
                ])->save();

                return true;
            });
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        }

        if (! $outcome) {
            return $this->notEditableResponse();
        }

        return $this->respondWithSuccess($request, 'Data mutasi berhasil diperbarui.');
    }

    /** Riwayat status terakhir yang sesuai status pegawai saat ini; null bila tidak boleh diedit di sini. */
    private function editableHistory(Data $staff): ?StaffStatusHistory
    {
        if (! in_array($staff->status, self::OUT_STATUSES, true) || $staff->status === 'retired') {
            return null;
        }

        return StaffStatusHistory::with('documents.category')
            ->where('staff_id', $staff->id)
            ->where('to_status', $staff->status)
            ->orderByDesc('effective_date')
            ->orderByDesc('created_at')
            ->first();
    }

    private function notEditableResponse()
    {
        return response('', 204)->header('HX-Trigger', json_encode([
            'showAlert' => [
                'icon' => 'warning',
                'title' => 'Tidak dapat diubah',
                'text' => 'Data mutasi pegawai ini tidak dapat diubah dari halaman ini.',
            ],
            'refreshStaffTransferData' => true,
        ]));
    }

    /*
    |--------------------------------------------------------------------
    | Riwayat status & berkas SK
    |--------------------------------------------------------------------
    */

    /**
     * Catat perubahan status beserta berkas SK-nya (jika ada) dalam satu
     * transaksi. Status & TMT di staff_data diperbarui otomatis oleh model
     * StaffStatusHistory. Jika berkas ditolak, perubahan status ikut dibatalkan.
     */
    private function recordStatusChange(Data $staff, string $toStatus, array $validated, Request $request): void
    {
        $history = StaffStatusHistory::create([
            'staff_id'       => $staff->id,
            'from_status'    => $staff->status,
            'to_status'      => $toStatus,
            'decree_number'  => $validated['decree_number'],
            'decree_date'    => $validated['decree_date'],
            'effective_date' => $validated['effective_date'],
            'note'           => $validated['note'] ?? null,
            'created_by'     => Auth::id(),
        ]);

        if ($request->hasFile('file')) {
            $history->attachDocument($request->file('file'), self::SK_CATEGORY[$toStatus]);
        }

        $staff->update(['updated_by' => Auth::id()]);
    }

    /**
     * Tempelkan berkas dari peristiwa status TERAKHIR (yang to_status-nya sama
     * dengan status pegawai saat ini) sebagai relasi 'latestStatusDocuments'.
     */
    private function attachLatestDocuments($staffList): void
    {
        $histories = StaffStatusHistory::with('documents.category')
            ->whereIn('staff_id', $staffList->pluck('id'))
            ->orderByDesc('effective_date')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('staff_id');

        $staffList->getCollection()->each(function ($staff) use ($histories) {
            $latest = $histories->get($staff->id)?->first(fn($h) => $h->to_status === $staff->status);

            $staff->setRelation('latestStatusDocuments', $latest?->documents ?? collect());
        });
    }

    /*
    |--------------------------------------------------------------------
    | Validasi
    |--------------------------------------------------------------------
    */

    /** Aturan bersama Mutasi & Reaktivasi; $specific = aturan khusus tiap mode. */
    private function rules(array $specific): array
    {
        return $specific + [
            'decree_number'  => ['required', 'string', 'max:255'],
            'decree_date'    => ['required', 'date'],
            'effective_date' => ['required', 'date'],
            'note'           => ['nullable', 'string', 'max:1000'],
            'file'           => ['nullable', 'file', 'mimes:pdf', 'max:' . StaffDocumentService::MAX_SIZE_KB],
        ];
    }

    private function messages(array $extra = []): array
    {
        return $extra + [
            'required'      => ':attribute wajib diisi.',
            'date'          => ':attribute harus berupa tanggal yang valid.',
            'file.mimes'    => 'Berkas SK harus berformat PDF.',
            'file.max'      => 'Ukuran berkas SK maksimal 1 MB.',
            'file.uploaded' => 'Berkas SK gagal diunggah. Pastikan ukurannya maksimal 1 MB.',
        ];
    }

    private function attributes(): array
    {
        return [
            'staff_id'       => 'Pegawai',
            'to_status'      => 'Status tujuan',
            'decree_number'  => 'Nomor SK',
            'decree_date'    => 'Tanggal SK',
            'effective_date' => 'TMT',
            'file'           => 'Berkas SK',
        ];
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
        // Pesan dari service berkas (kunci selain field form) ditampilkan di bawah field berkas.
        $known = ['staff_id', 'to_status', 'decree_number', 'decree_date', 'effective_date', 'note', 'file'];
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
}
