<?php

namespace App\Http\Controllers\Admin\Career;

use App\Http\Controllers\Controller;
use App\Models\Data;
use App\Models\Grade;
use App\Models\GradeHistory;
use App\Services\Staff\StaffDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GradeHistoryController extends Controller
{
    /** Kode kategori dokumen untuk SK pangkat (tabel staff_document_categories). */
    private const SK_CATEGORY = 'sk_pangkat';

    public function index(Request $request)
    {
        $search = $request->search;

        // Mengambil data riwayat pangkat beserta relasi pegawai dan master golongan
        $query = GradeHistory::with(['staff', 'grade'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->whereHas('staff', fn($s) => $s->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('grade', function ($g) use ($search) {
                            $g->where('grade_name', 'like', "%{$search}%")
                                ->orWhere('grade_code', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('effective_date', 'desc');

        $histories = $query->paginate(10)->withQueryString();

        // Jika request dari HTMX (pencarian/pagination), kembalikan partial table saja
        if ($request->header('HX-Request')) {
            return view('pages.admin.career.grades.partials._table', compact('histories'));
        }

        return view('pages.admin.career.grades.index', compact('histories', 'search'));
    }

    public function show(Request $request, $id)
    {
        // 1. Ambil data pegawai beserta relasinya
        $staff = Data::with(['vault', 'employmentStatus'])->findOrFail($id);

        // 2. Ambil data riwayat kepangkatan khusus untuk pegawai ini (beserta berkas SK-nya)
        $histories = GradeHistory::with(['grade', 'documents.category'])
            ->where('staff_id', $id)
            ->orderBy('effective_date', 'desc')
            ->paginate(10);

        // 3. Jika request dari HTMX, kembalikan partial tabel show-nya
        if ($request->header('HX-Request')) {
            return view('pages.admin.career.grades.show.partials._table', compact('staff', 'histories'));
        }

        // 4. Kembalikan view utama show
        return view('pages.admin.career.grades.show.index', compact('staff', 'histories'));
    }

    public function create($id)
    {
        $staff = Data::findOrFail($id);
        $grades = Grade::orderBy('grade_code')->get();

        return view('pages.admin.career.grades.show.partials._modal-form', compact('staff', 'grades'));
    }

    public function store(Request $request, $id)
    {
        $staff = Data::findOrFail($id);

        try {
            $validated = $this->validated($request, $staff);

            // Data pangkat dan berkas SK disimpan dalam satu transaksi:
            // jika berkas gagal, data pangkat ikut dibatalkan.
            DB::transaction(function () use ($request, $staff, $validated) {
                $history = GradeHistory::create(
                    Arr::except($validated, ['file']) + [
                        'staff_id'   => $staff->id,
                        'created_by' => Auth::id(),
                    ]
                );

                $this->storeSk($history, $request);
            });
        } catch (ValidationException $e) {
            return $this->respondWithModalErrors($request, $e, $staff, null);
        }

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

        return view('pages.admin.career.grades.show.partials._modal-form', compact('staff', 'history', 'grades'));
    }

    public function update(Request $request, $staff_id, $history_id)
    {
        $staff = Data::findOrFail($staff_id);
        $history = GradeHistory::where('staff_id', $staff_id)->findOrFail($history_id);

        try {
            $validated = $this->validated($request, $staff, $history_id);

            DB::transaction(function () use ($request, $history, $validated) {
                $history->update(
                    Arr::except($validated, ['file']) + ['updated_by' => Auth::id()]
                );

                // Nomor & tanggal SK di berkas mengikuti data pangkat (sumber kebenaran)
                $this->skDocuments($history)->update([
                    'document_number' => $history->decree_number,
                    'document_date'   => $history->decree_date,
                    'updated_by'      => Auth::id(),
                ]);

                $this->storeSk($history, $request);
            });
        } catch (ValidationException $e) {
            return $this->respondWithModalErrors($request, $e, $staff, $history);
        }

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

            // Berkas SK ikut dihapus lunak lewat trait HasStaffDocuments
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

    /*
    |--------------------------------------------------------------------
    | Helper
    |--------------------------------------------------------------------
    */

    /**
     * Aturan validasi bersama untuk tambah & ubah. $ignoreId diisi saat ubah
     * supaya TMT milik baris yang sedang diedit tidak dianggap duplikat.
     */
    private function validated(Request $request, Data $staff, ?string $ignoreId = null): array
    {
        $unique = Rule::unique('staff_grade_histories')
            ->where(fn($query) => $query->where('staff_id', $staff->id));

        if ($ignoreId) {
            $unique->ignore($ignoreId);
        }

        return $request->validate([
            'grade_id'             => 'required|exists:staff_grades,id',
            'effective_date'       => ['required', 'date', $unique],
            'decree_number'        => ['nullable', 'string', 'max:255', 'required_with:file'],
            'decree_date'          => 'nullable|date',
            'service_period_years' => 'nullable|integer|min:0',
            'service_period_months' => 'nullable|integer|min:0|max:11',
            'approval_reference'   => 'nullable|string|max:255',
            'approval_date'        => 'nullable|date',
            'position_at_time'     => 'nullable|string|max:255',
            'file'                 => ['nullable', 'file', 'mimes:pdf', 'max:' . StaffDocumentService::MAX_SIZE_KB],
        ], [
            'grade_id.required'              => 'Golongan wajib dipilih.',
            'grade_id.exists'                => 'Golongan yang dipilih tidak valid.',
            'effective_date.required'        => 'TMT Pangkat wajib diisi.', // <-- TAMBAHKAN INI
            'effective_date.date'            => 'Format TMT Pangkat tidak valid.', // <-- OPSIONAL TAMBAHAN
            'effective_date.unique'          => 'Riwayat kepangkatan pada TMT (Tanggal) tersebut sudah ada untuk pegawai ini.',
            'decree_number.required_with'    => 'Nomor SK wajib diisi jika mengunggah berkas SK.',
            'file.mimes'                     => 'Berkas SK harus berformat PDF.',
            'file.max'                       => 'Ukuran berkas SK maksimal 1 MB.',
            'file.uploaded'                  => 'Berkas SK gagal diunggah. Pastikan ukurannya maksimal 1 MB.',
        ]);
    }

    /** Query berkas SK pangkat milik satu riwayat. */
    private function skDocuments(GradeHistory $history)
    {
        return $history->documents()
            ->whereHas('category', fn($c) => $c->where('code', self::SK_CATEGORY));
    }

    /**
     * Simpan berkas SK jika ada yang diunggah. Satu riwayat hanya punya satu
     * SK aktif: berkas lama (jika ada) dihapus lunak lalu digantikan.
     */
    private function storeSk(GradeHistory $history, Request $request): void
    {
        if (! $request->hasFile('file')) {
            return;
        }

        $this->skDocuments($history)->get()->each->delete();

        $history->attachDocument($request->file('file'), self::SK_CATEGORY);
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

            // @error() di blade memanggil $errors->getBag(), jadi harus ViewErrorBag
            // (bukan MessageBag mentah dari validator).
            $errorBag = new ViewErrorBag();
            $errorBag->put('default', $e->validator->errors());

            return response()
                ->view('pages.admin.career.grades.show.partials._modal-form', [
                    'staff'   => $staff,
                    'history' => $history,
                    'grades'  => Grade::orderBy('grade_code')->get(),
                    'errors'  => $errorBag,
                ], 200)
                ->header('HX-Retarget', '#modal-container')
                ->header('HX-Reswap', 'innerHTML');
        }

        throw $e;
    }
}
