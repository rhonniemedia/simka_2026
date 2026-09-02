<?php

namespace App\Http\Controllers\Admin\Personnel;

use App\Http\Controllers\Controller;
use App\Models\Data;
use App\Models\Grade;
use App\Models\GradeHistory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GradeHistoryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        // Mengambil data riwayat pangkat beserta relasi pegawai dan master golongan
        $query = GradeHistory::with(['staff', 'grade'])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('staff', function ($qStaff) use ($search) {
                    $qStaff->where('name', 'like', "%{$search}%");
                })
                    ->orWhereHas('grade', function ($qGrade) use ($search) {
                        $qGrade->where('name', 'like', "%{$search}%");
                    });
            })
            ->orderBy('effective_date', 'desc');

        $histories = $query->paginate(10)->withQueryString();

        // Jika request dari HTMX (pencarian/pagination), kembalikan partial table saja
        if ($request->header('HX-Request')) {
            return view('pages.admin.personnel.grade.partials._table', compact('histories'));
        }

        return view('pages.admin.personnel.grade.index', compact('histories', 'search'));
    }

    public function show(Request $request, $id)
    {
        // 1. Ambil data pegawai beserta relasinya
        $staff = Data::with(['vault', 'employmentStatus'])->findOrFail($id);

        // 2. Ambil data riwayat kepangkatan khusus untuk pegawai ini
        $histories = GradeHistory::with(['grade'])
            ->where('staff_id', $id)
            ->orderBy('effective_date', 'desc')
            ->paginate(10);

        // 3. Jika request dari HTMX, kembalikan partial tabel show-nya
        if ($request->header('HX-Request')) {
            return view('pages.admin.personnel.grade.show.partials._table', compact('staff', 'histories'));
        }

        // 4. Kembalikan view utama show
        return view('pages.admin.personnel.grade.show.index', compact('staff', 'histories'));
    }

    public function create($id)
    {
        $staff = Data::findOrFail($id);
        $grades = Grade::orderBy('grade_code')->get();

        return view('pages.admin.personnel.grade.show.partials._modal-form', compact('staff', 'grades'));
    }

    public function store(Request $request, $id)
    {
        $staff = Data::findOrFail($id);

        try {
            $validated = $request->validate([
                'grade_id' => 'required|exists:staff_grades,id',
                'effective_date' => [
                    'required',
                    'date',
                    Rule::unique('staff_grade_histories')->where(function ($query) use ($staff) {
                        return $query->where('staff_id', $staff->id);
                    }),
                ],
                'decree_number' => 'nullable|string|max:255',
                'decree_date' => 'nullable|date',
                'service_period_years' => 'nullable|integer|min:0',
                'approval_reference' => 'nullable|string|max:255',
                'approval_date' => 'nullable|date',
                'position_at_time' => 'nullable|string|max:255',
            ], [
                'grade_id.required' => 'Golongan wajib dipilih.',
                'grade_id.exists' => 'Golongan yang dipilih tidak valid.',
                'effective_date.unique' => 'Riwayat kepangkatan pada TMT (Tanggal) tersebut sudah ada untuk pegawai ini.',
            ]);
        } catch (ValidationException $e) {
            return $this->respondWithModalErrors($request, $e, $staff, null);
        }

        $validated['staff_id'] = $staff->id;
        $validated['created_by'] = auth()->id();

        GradeHistory::create($validated);

        if ($request->header('HX-Request')) {
            return response($this->show($request, $id)->render())
                ->header('HX-Trigger', json_encode([
                    'close-modal' => true,
                    'showAlert' => [
                        'icon'  => 'success',
                        'title' => 'Berhasil!',
                        'text'  => 'Data kepangkatan berhasil ditambahkan.',
                    ],
                ]));
        }

        return redirect()->route('admin.personnel.promotions.show', $id);
    }

    public function edit($staff_id, $history_id)
    {
        $staff = Data::findOrFail($staff_id);
        $history = GradeHistory::where('staff_id', $staff_id)->findOrFail($history_id);
        $grades = Grade::orderBy('grade_code')->get();

        return view('pages.admin.personnel.grade.show.partials._modal-form', compact('staff', 'history', 'grades'));
    }

    public function update(Request $request, $staff_id, $history_id)
    {
        $staff = Data::findOrFail($staff_id);
        $history = GradeHistory::where('staff_id', $staff_id)->findOrFail($history_id);

        try {
            $validated = $request->validate([
                'grade_id' => 'required|exists:staff_grades,id',
                'effective_date' => [
                    'required',
                    'date',
                    Rule::unique('staff_grade_histories')->where(function ($query) use ($staff) {
                        return $query->where('staff_id', $staff->id);
                    })->ignore($history_id),
                ],
                'decree_number' => 'nullable|string|max:255',
                'decree_date' => 'nullable|date',
                'service_period_years' => 'nullable|integer|min:0',
                'approval_reference' => 'nullable|string|max:255',
                'approval_date' => 'nullable|date',
                'position_at_time' => 'nullable|string|max:255',
            ], [
                'grade_id.required' => 'Golongan wajib dipilih.',
                'grade_id.exists' => 'Golongan yang dipilih tidak valid.',
                'effective_date.unique' => 'Riwayat kepangkatan pada TMT (Tanggal) tersebut sudah ada untuk pegawai ini.',
            ]);
        } catch (ValidationException $e) {
            return $this->respondWithModalErrors($request, $e, $staff, $history);
        }

        $validated['updated_by'] = auth()->id();

        $history->update($validated);

        if ($request->header('HX-Request')) {
            return response($this->show($request, $staff_id)->render())
                ->header('HX-Trigger', json_encode([
                    'close-modal' => true,
                    'showAlert' => [
                        'icon'  => 'success',
                        'title' => 'Berhasil!',
                        'text'  => 'Data kepangkatan berhasil diperbarui.',
                    ],
                ]));
        }

        return redirect()->route('admin.personnel.promotions.show', $staff_id);
    }

    public function destroy(Request $request, $staff_id, $history_id)
    {
        try {
            $history = GradeHistory::where('staff_id', $staff_id)->findOrFail($history_id);
            $history->delete();
        } catch (\Throwable $e) {
            report($e);

            if ($request->header('HX-Request')) {
                // Render ulang tabel apa adanya (data belum berubah) supaya swap
                // outerHTML di sisi klien tidak mengosongkan tabel, lalu tampilkan
                // alert error lewat HX-Trigger.
                return response($this->show($request, $staff_id)->render())
                    ->header('HX-Trigger', json_encode([
                        'showAlert' => [
                            'icon'  => 'error',
                            'title' => 'Gagal!',
                            'text'  => 'Riwayat kepangkatan gagal dihapus. Silakan coba lagi.',
                        ],
                    ]));
            }

            return redirect()->route('admin.personnel.promotions.show', $staff_id)
                ->with('error', 'Riwayat kepangkatan gagal dihapus.');
        }

        if ($request->header('HX-Request')) {
            // Render ulang tabel dengan data terbaru (baris yang dihapus sudah tidak ada)
            // dan kirim sebagai body response, supaya tombol hapus (swap: outerHTML)
            // langsung mengganti tabel lama dengan tabel baru tanpa perlu event tambahan.
            return response($this->show($request, $staff_id)->render())
                ->header('HX-Trigger', json_encode([
                    'showAlert' => [
                        'icon'  => 'success',
                        'title' => 'Berhasil!',
                        'text'  => 'Riwayat kepangkatan berhasil dihapus.',
                    ],
                ]));
        }

        return redirect()->route('admin.personnel.promotions.show', $staff_id);
    }

    /**
     * Render ulang modal form beserta pesan error validasi, lalu
     * paksa htmx untuk swap ke #modal-container (bukan target asli form)
     * memakai header HX-Retarget/HX-Reswap. Ini mencegah error redirect
     * bawaan Laravel "menghancurkan" tampilan saat request datang dari htmx.
     */
    protected function respondWithModalErrors(Request $request, ValidationException $e, Data $staff, ?GradeHistory $history)
    {
        if ($request->header('HX-Request')) {
            // Supaya old() bisa dipakai di blade untuk mempertahankan input user
            $request->flash();

            return response()
                ->view('pages.admin.personnel.grade.show.partials._modal-form', [
                    'staff'   => $staff,
                    'history' => $history,
                    'grades'  => Grade::orderBy('grade_code')->get(),
                    'errors'  => $e->validator->errors(),
                ], 200)
                ->header('HX-Retarget', '#modal-container')
                ->header('HX-Reswap', 'innerHTML');
        }

        throw $e;
    }
}
