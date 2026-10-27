<?php

namespace App\Http\Controllers\Admin\Career;

use App\Http\Controllers\Controller;
use App\Models\AsnPosition;
use App\Models\AsnPositionHistory;
use App\Models\Data;
use App\Services\Staff\StaffDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PositionHistoryController extends Controller
{
    private const INDEX_TABLE_VIEW = 'pages.admin.career.positions.partials._table';

    private const SHOW_TABLE_VIEW = 'pages.admin.career.positions.show.partials._table';
    private const FORM_MODAL_VIEW = 'pages.admin.career.positions.show.partials._modal-form';

    /** Kode kategori dokumen untuk SK jabatan (tabel staff_document_categories). */
    private const SK_CATEGORY = 'sk_jabatan';

    /*
    |--------------------------------------------------------------------
    | 1. ROSTER (daftar pegawai)
    |--------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $search = $request->input('search');
        $filterEmploymentStatus = $request->input('filter_employment_status');
        $filterActiveOnly = $request->boolean('filter_active_only');

        $query = Data::completed()
            ->select('staff_data.*')
            ->leftJoin('staff_asn_positions_histories as active_history', function ($join) {
                $join->on('active_history.staff_id', '=', 'staff_data.id')
                    ->where('active_history.is_active', true);
            })
            ->addSelect([
                'active_position_name' => AsnPosition::select('name')
                    ->whereColumn('id', 'active_history.staff_asn_position_id')
                    ->limit(1),
                'active_history.effective_date as active_position_effective_date',
            ])
            ->selectSub(
                AsnPositionHistory::selectRaw('count(*)')->whereColumn('staff_id', 'staff_data.id'),
                'position_history_count'
            )
            ->with(['employmentStatus', 'vault']);

        if (!empty($search)) {
            $query->where('staff_data.name', 'like', "%{$search}%");
        }

        if (!empty($filterEmploymentStatus)) {
            $query->where('staff_data.employment_id', $filterEmploymentStatus);
        }

        if ($filterActiveOnly) {
            $query->whereNotNull('active_history.id');
        }

        $staffList = $query->orderBy('staff_data.name')->paginate(10)->withQueryString();

        // Berkas SK jabatan aktif tiap pegawai di halaman ini (kolom dokumen roster)
        $this->attachActiveSk($staffList);

        if ($request->header('HX-Request')) {
            return view(self::INDEX_TABLE_VIEW, compact('staffList'));
        }

        $employmentOptions = \App\Models\EmploymentStatus::orderBy('name')->get();

        return view('pages.admin.career.positions.index', compact(
            'staffList',
            'search',
            'filterEmploymentStatus',
            'filterActiveOnly',
            'employmentOptions'
        ));
    }

    /*
    |--------------------------------------------------------------------
    | 2. DETAIL (riwayat jabatan milik satu pegawai)
    |--------------------------------------------------------------------
    */

    public function show(Request $request, string $id)
    {
        $staff = Data::with('employmentStatus')->completed()->findOrFail($id);

        $histories = AsnPositionHistory::with(['asnPosition', 'documents.category'])
            ->where('staff_id', $id)
            ->orderByDesc('effective_date')
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        if ($request->header('HX-Request')) {
            return view(self::SHOW_TABLE_VIEW, compact('staff', 'histories'));
        }

        $activePosition = AsnPositionHistory::with('asnPosition')
            ->where('staff_id', $id)
            ->where('is_active', true)
            ->first();

        return view('pages.admin.career.positions.show.index', compact('staff', 'histories', 'activePosition'));
    }

    /*
    |--------------------------------------------------------------------
    | 3. Form: tampilkan modal (tambah & edit)
    |--------------------------------------------------------------------
    */

    public function create(string $id)
    {
        $staff = Data::completed()->findOrFail($id);

        return view(self::FORM_MODAL_VIEW, [
            'staff' => $staff,
            'history' => new AsnPositionHistory(),
            'positionOptions' => AsnPosition::orderBy('name')->get(),
        ]);
    }

    public function edit(string $staff_id, string $history_id)
    {
        $staff = Data::completed()->findOrFail($staff_id);
        $history = AsnPositionHistory::where('staff_id', $staff_id)->findOrFail($history_id);

        return view(self::FORM_MODAL_VIEW, [
            'staff' => $staff,
            'history' => $history,
            'positionOptions' => AsnPosition::orderBy('name')->get(),
        ]);
    }

    /*
    |--------------------------------------------------------------------
    | 4. Simpan
    |--------------------------------------------------------------------
    */

    public function store(Request $request, string $id)
    {
        $staff = Data::completed()->findOrFail($id);

        try {
            $validated = $this->validateHistory($request, $staff->id);

            // Data jabatan dan berkas SK disimpan dalam satu transaksi:
            // jika berkas gagal, data jabatan ikut dibatalkan.
            DB::transaction(function () use ($request, $staff, $validated) {
                if ($validated['is_active']) {
                    AsnPositionHistory::where('staff_id', $staff->id)
                        ->where('is_active', true)
                        ->update(['is_active' => false]);
                }

                $history = AsnPositionHistory::create(
                    Arr::except($validated, ['file']) + [
                        'staff_id'   => $staff->id,
                        'created_by' => Auth::id(),
                    ]
                );

                $this->storeSk($history, $request);
            });
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        }

        return $this->respondWithSuccess($request, $staff->id, 'Riwayat jabatan ASN berhasil ditambahkan.');
    }

    public function update(Request $request, string $staff_id, string $history_id)
    {
        $staff = Data::completed()->findOrFail($staff_id);
        $history = AsnPositionHistory::where('staff_id', $staff_id)->findOrFail($history_id);

        try {
            $validated = $this->validateHistory($request, $staff->id, $history->id);

            DB::transaction(function () use ($request, $staff, $history, $validated) {
                if ($validated['is_active']) {
                    AsnPositionHistory::where('staff_id', $staff->id)
                        ->where('is_active', true)
                        ->where('id', '!=', $history->id)
                        ->update(['is_active' => false]);
                }

                $history->update(
                    Arr::except($validated, ['file']) + ['updated_by' => Auth::id()]
                );

                $this->syncSkMeta($history);
                $this->storeSk($history, $request);
            });
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        }

        return $this->respondWithSuccess($request, $staff->id, 'Riwayat jabatan ASN berhasil diperbarui.');
    }

    public function destroy(Request $request, string $staff_id, string $history_id)
    {
        $history = AsnPositionHistory::where('staff_id', $staff_id)->findOrFail($history_id);

        // Berkas SK ikut dihapus lunak lewat trait HasStaffDocuments
        $history->delete();

        if ($request->header('HX-Request')) {
            return $this->historyTable($request, $staff_id);
        }

        return redirect()->route('admin.personnel.positions.show', $staff_id);
    }

    /*
    |--------------------------------------------------------------------
    | Berkas SK
    |--------------------------------------------------------------------
    */

    /**
     * Tempelkan berkas SK jabatan AKTIF milik tiap pegawai di halaman ini
     * sebagai relasi 'activeSk' (dipakai kolom dokumen di tabel roster).
     */
    private function attachActiveSk($staffList): void
    {
        $activeHistories = AsnPositionHistory::with(['documents' => function ($q) {
            $q->whereHas('category', fn($c) => $c->where('code', self::SK_CATEGORY))->latest();
        }])
            ->whereIn('staff_id', $staffList->pluck('id'))
            ->where('is_active', true)
            ->get()
            ->keyBy('staff_id');

        $staffList->getCollection()->each(function ($staff) use ($activeHistories) {
            $staff->setRelation('activeSk', $activeHistories->get($staff->id)?->documents->first());
        });
    }

    /** Query berkas SK jabatan milik satu riwayat. */
    private function skDocuments(AsnPositionHistory $history)
    {
        return $history->documents()
            ->whereHas('category', fn($c) => $c->where('code', self::SK_CATEGORY));
    }

    /**
     * Nomor & tanggal SK di berkas mengikuti data jabatan (sumber kebenaran).
     * Hanya nilai yang terisi yang disalin.
     */
    private function syncSkMeta(AsnPositionHistory $history): void
    {
        $meta = array_filter([
            'document_number' => $history->decree_number,
            'document_date'   => $history->decree_date,
        ], fn($value) => filled($value));

        if ($meta) {
            $this->skDocuments($history)->update($meta + ['updated_by' => Auth::id()]);
        }
    }

    /**
     * Simpan berkas SK jika ada yang diunggah. Satu riwayat hanya punya satu
     * SK aktif: berkas lama (jika ada) dihapus lunak lalu digantikan.
     */
    private function storeSk(AsnPositionHistory $history, Request $request): void
    {
        if (! $request->hasFile('file')) {
            return;
        }

        $this->skDocuments($history)->get()->each->delete();

        $history->attachDocument($request->file('file'), self::SK_CATEGORY);
    }

    /*
    |--------------------------------------------------------------------
    | Validasi & respons
    |--------------------------------------------------------------------
    */

    private function validateHistory(Request $request, string $staffId, ?string $ignoreId = null): array
    {
        // Aturan agar TMT (effective_date) tidak duplikat untuk pegawai yang sama
        $uniqueEffectiveDate = Rule::unique('staff_asn_positions_histories', 'effective_date')
            ->where(fn($query) => $query->where('staff_id', $staffId));

        if ($ignoreId) {
            $uniqueEffectiveDate->ignore($ignoreId);
        }

        // Aturan agar Nomor SK (decree_number) tidak duplikat untuk pegawai yang sama
        $uniqueDecreeNumber = Rule::unique('staff_asn_positions_histories', 'decree_number')
            ->where(fn($query) => $query->where('staff_id', $staffId));

        if ($ignoreId) {
            $uniqueDecreeNumber->ignore($ignoreId);
        }

        $validated = $request->validate(
            [
                'staff_asn_position_id' => ['required', 'uuid', Rule::exists('staff_asn_positions', 'id')],
                'decree_number' => ['required', 'string', 'max:255', $uniqueDecreeNumber],
                'decree_date' => ['required', 'date'],
                'effective_date' => ['required', 'date', 'after_or_equal:decree_date', $uniqueEffectiveDate],
                'is_active' => ['nullable', 'boolean'],
                'file' => [
                    'nullable',
                    'file',
                    'mimes:pdf',
                    'max:' . StaffDocumentService::MAX_SIZE_KB,
                    // Validasi tambahan agar berkas SK tidak ganda berdasarkan nama file yang sama pada pegawai ini
                    function ($attribute, $value, $fail) use ($request, $staffId, $ignoreId) {
                        if ($value) {
                            $originalName = $value->getClientOriginalName();

                            $query = DB::table('staff_documents')
                                ->where('staff_id', $staffId)
                                ->where('original_filename', $originalName);

                            // Jika mode edit, abaikan dokumen dari riwayat yang sedang diedit ini sendiri
                            if ($ignoreId) {
                                $history = AsnPositionHistory::find($ignoreId);
                                if ($history) {
                                    $documentIds = $history->documents()->pluck('staff_documents.id');
                                    $query->whereNotIn('id', $documentIds);
                                }
                            }

                            if ($query->exists()) {
                                $fail('Berkas dengan nama file yang sama sudah pernah diunggah sebelumnya untuk pegawai ini.');
                            }
                        }
                    },
                ],
            ],
            [
                'required' => ':attribute wajib diisi.',
                'exists' => ':attribute yang dipilih tidak valid.',
                'date' => ':attribute harus berupa tanggal yang valid.',
                'after_or_equal' => ':attribute tidak boleh sebelum tanggal SK.',
                'effective_date.unique' => 'Riwayat jabatan dengan TMT (Tanggal) tersebut sudah ada untuk pegawai ini.',
                'decree_number.unique' => 'Nomor SK tersebut sudah pernah diinput sebelumnya untuk pegawai ini.',
                'file.mimes' => 'Berkas SK harus berformat PDF.',
                'file.max' => 'Ukuran berkas SK maksimal 1 MB.',
                'file.uploaded' => 'Berkas SK gagal diunggah. Pastikan ukurannya maksimal 1 MB.',
            ],
            [
                'staff_asn_position_id' => 'Jabatan ASN',
                'decree_number' => 'Nomor SK',
                'decree_date' => 'Tanggal SK',
                'effective_date' => 'TMT (tanggal efektif)',
                'is_active' => 'Status aktif',
                'file' => 'Berkas SK',
            ]
        );

        // Checkbox: kirim 'on'/'1' bila dicentang, tidak terkirim sama sekali bila tidak.
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    private function historyTable(Request $request, string $staffId): string
    {
        $histories = AsnPositionHistory::with(['asnPosition', 'documents.category'])
            ->where('staff_id', $staffId)
            ->orderByDesc('effective_date')
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        $staff = Data::with('employmentStatus')->completed()->findOrFail($staffId);

        return view(self::SHOW_TABLE_VIEW, compact('staff', 'histories'))->render();
    }

    private function respondWithSuccess(Request $request, string $staffId, string $message)
    {
        if ($request->header('HX-Request')) {
            return response($this->historyTable($request, $staffId))
                ->header('HX-Trigger', json_encode([
                    'close-modal' => true,
                    'showAlert' => [
                        'icon' => 'success',
                        'title' => 'Berhasil!',
                        'text' => $message,
                    ],
                ]));
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }

        return redirect()->route('admin.personnel.positions.show', $staffId);
    }

    private function validationErrorResponse(ValidationException $e): JsonResponse
    {
        return response()->json([
            'message' => $e->getMessage(),
            'errors' => $e->errors(),
        ], $e->status);
    }
}
