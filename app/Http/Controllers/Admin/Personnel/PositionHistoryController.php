<?php

namespace App\Http\Controllers\Admin\Personnel;

use App\Http\Controllers\Controller;
use App\Models\AsnPosition;
use App\Models\AsnPositionHistory;
use App\Models\Data;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PositionHistoryController extends Controller
{
    private const INDEX_TABLE_VIEW = 'pages.admin.personnel.positions.partials._table';

    private const SHOW_TABLE_VIEW = 'pages.admin.personnel.positions.show.partials._table';
    private const FORM_MODAL_VIEW = 'pages.admin.personnel.positions.show.partials._modal-form';

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

        if ($request->header('HX-Request')) {
            return view(self::INDEX_TABLE_VIEW, compact('staffList'));
        }

        $employmentOptions = \App\Models\EmploymentStatus::orderBy('name')->get();

        return view('pages.admin.personnel.positions.index', compact(
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

        $histories = AsnPositionHistory::with('asnPosition')
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

        return view('pages.admin.personnel.positions.show.index', compact('staff', 'histories', 'activePosition'));
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
            $validated = $this->validateHistory($request);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        }

        DB::transaction(function () use ($staff, $validated) {
            if ($validated['is_active']) {
                AsnPositionHistory::where('staff_id', $staff->id)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }

            AsnPositionHistory::create(array_merge($validated, ['staff_id' => $staff->id]));
        });

        return $this->respondWithSuccess($request, $staff->id, 'Riwayat jabatan ASN berhasil ditambahkan.');
    }

    public function update(Request $request, string $staff_id, string $history_id)
    {
        $staff = Data::completed()->findOrFail($staff_id);
        $history = AsnPositionHistory::where('staff_id', $staff_id)->findOrFail($history_id);

        try {
            $validated = $this->validateHistory($request);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        }

        DB::transaction(function () use ($staff, $history, $validated) {
            if ($validated['is_active']) {
                AsnPositionHistory::where('staff_id', $staff->id)
                    ->where('is_active', true)
                    ->where('id', '!=', $history->id)
                    ->update(['is_active' => false]);
            }

            $history->update($validated);
        });

        return $this->respondWithSuccess($request, $staff->id, 'Riwayat jabatan ASN berhasil diperbarui.');
    }

    public function destroy(Request $request, string $staff_id, string $history_id)
    {
        $history = AsnPositionHistory::where('staff_id', $staff_id)->findOrFail($history_id);
        $history->delete();

        if ($request->header('HX-Request')) {
            return $this->historyTable($request, $staff_id);
        }

        return redirect()->route('admin.personnel.positions.show', $staff_id);
    }

    /*
    |--------------------------------------------------------------------
    | Validasi & respons
    |--------------------------------------------------------------------
    */

    private function validateHistory(Request $request): array
    {
        $validated = $request->validate(
            [
                'staff_asn_position_id' => ['required', 'uuid', Rule::exists('staff_asn_positions', 'id')],
                'decree_number' => ['required', 'string', 'max:255'],
                'decree_date' => ['required', 'date'],
                'effective_date' => ['required', 'date', 'after_or_equal:decree_date'],
                'is_active' => ['nullable', 'boolean'],
            ],
            [
                'required' => ':attribute wajib diisi.',
                'exists' => ':attribute yang dipilih tidak valid.',
                'date' => ':attribute harus berupa tanggal yang valid.',
                'after_or_equal' => ':attribute tidak boleh sebelum tanggal SK.',
            ],
            [
                'staff_asn_position_id' => 'Jabatan ASN',
                'decree_number' => 'Nomor SK',
                'decree_date' => 'Tanggal SK',
                'effective_date' => 'TMT (tanggal efektif)',
                'is_active' => 'Status aktif',
            ]
        );

        // Checkbox: kirim 'on'/'1' bila dicentang, tidak terkirim sama sekali bila tidak.
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    private function historyTable(Request $request, string $staffId): string
    {
        $histories = AsnPositionHistory::with('asnPosition')
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
