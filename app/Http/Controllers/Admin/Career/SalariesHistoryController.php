<?php

namespace App\Http\Controllers\Admin\Career;

use App\Http\Controllers\Controller;
use App\Models\Data;
use App\Models\PeriodicSalaryHistory;
use App\Services\Staff\StaffDocumentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SalariesHistoryController extends Controller
{
    /** Kode kategori dokumen untuk SK berkala (tabel staff_document_categories). */
    private const SK_CATEGORY = 'sk_berkala';

    public function index(Request $request)
    {
        // Mengambil data gaji berkala beserta data pegawai terkait (dan berkas SK-nya)
        $histories = PeriodicSalaryHistory::with(['staff.vault', 'documents.category'])
            ->latest('effective_date')
            ->paginate(10);

        // Transformasi data untuk menghitung masa kerja dan KGB berikutnya
        $histories->getCollection()->transform(function ($history) {
            $tanggalMulaiTugas = $history->staff->prior_service_period_effective_date ?? null;

            if ($history->effective_date && $tanggalMulaiTugas) {
                $tmt = Carbon::parse($history->effective_date);
                $tanggalAwal = Carbon::parse($tanggalMulaiTugas);

                $masaKerjaBulan = $tanggalAwal->diffInMonths($tmt);
                $masaKerjaTahun = floor($masaKerjaBulan / 12);
                $months = $masaKerjaBulan % 12;

                $history->masa_kerja = sprintf("%02d Tahun %02d Bulan", $masaKerjaTahun, $months);

                if ($masaKerjaTahun <= 31) {
                    $history->tmt_berikut = $tmt->copy()->addYears(2)->format('Y-m-d');
                    $history->tahun_ke = $masaKerjaTahun + 2;
                } else {
                    $history->tmt_berikut = null;
                    $history->tahun_ke = null;
                }
            } else {
                $history->masa_kerja = '-';
                $history->tmt_berikut = null;
                $history->tahun_ke = '-';
            }

            return $history;
        });

        if ($request->header('HX-Request')) {
            return view('pages.admin.career.salaries.partials._table', compact('histories'));
        }

        return view('pages.admin.career.salaries.index', compact('histories'));
    }

    public function show(Request $request, $id)
    {
        $staff = Data::with(['vault', 'employmentStatus'])->findOrFail($id);

        $histories = PeriodicSalaryHistory::with('documents.category')
            ->where('staff_id', $id)
            ->latest('effective_date')
            ->paginate(10);

        $histories->getCollection()->transform(function ($history) use ($staff) {
            $tanggalMulaiTugas = $staff->prior_service_period_effective_date ?? null;

            if ($history->effective_date && $tanggalMulaiTugas) {
                $tmt = Carbon::parse($history->effective_date);
                $tanggalAwal = Carbon::parse($tanggalMulaiTugas);

                $masaKerjaBulan = $tanggalAwal->diffInMonths($tmt);
                $masaKerjaTahun = floor($masaKerjaBulan / 12);
                $months = $masaKerjaBulan % 12;

                $history->masa_kerja = sprintf("%02d Tahun %02d Bulan", $masaKerjaTahun, $months);

                if ($masaKerjaTahun <= 31) {
                    $history->tmt_berikut = $tmt->copy()->addYears(2)->format('Y-m-d');
                    $history->tahun_ke = $masaKerjaTahun + 2;
                } else {
                    $history->tmt_berikut = null;
                    $history->tahun_ke = null;
                }
            } else {
                $history->masa_kerja = '-';
                $history->tmt_berikut = null;
                $history->tahun_ke = '-';
            }

            return $history;
        });

        if ($request->header('HX-Request')) {
            return view('pages.admin.career.salaries.show.partials._table', compact('staff', 'histories'));
        }

        return view('pages.admin.career.salaries.show.index', compact('staff', 'histories'));
    }

    public function create($id)
    {
        $staff = Data::findOrFail($id);

        return view('pages.admin.career.salaries.show.partials._modal-form', compact('staff'));
    }

    public function store(Request $request, $id)
    {
        $staff = Data::findOrFail($id);

        try {
            $validated = $this->validated($request, $staff);

            // Data berkala dan berkas SK disimpan dalam satu transaksi:
            // jika berkas gagal, data berkala ikut dibatalkan.
            DB::transaction(function () use ($request, $staff, $validated) {
                // Model otomatis mendeteksi key 'base_salary', mengenkripsinya,
                // dan menyimpannya sebagai 'base_salary_encrypted'.
                $history = $staff->periodicSalaries()->create(
                    Arr::except($validated, ['file']) + ['created_by' => Auth::id()]
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
                        'text'  => 'Data gaji berkala berhasil ditambahkan.',
                    ],
                ]));
        }

        return redirect()->route('admin.personnel.periodic-salary.show', $id);
    }

    public function edit($staff_id, $history_id)
    {
        $staff = Data::findOrFail($staff_id);
        $history = PeriodicSalaryHistory::where('staff_id', $staff_id)->findOrFail($history_id);

        return view('pages.admin.career.salaries.show.partials._modal-form', compact('staff', 'history'));
    }

    public function update(Request $request, $staff_id, $history_id)
    {
        $staff = Data::findOrFail($staff_id);
        $history = PeriodicSalaryHistory::where('staff_id', $staff_id)->findOrFail($history_id);

        try {
            $validated = $this->validated($request, $staff, $history_id);

            DB::transaction(function () use ($request, $history, $validated) {
                $history->update(
                    Arr::except($validated, ['file']) + ['updated_by' => Auth::id()]
                );

                $this->syncSkMeta($history);
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
                        'text'  => 'Data gaji berkala berhasil diperbarui.',
                    ],
                ]));
        }

        return redirect()->route('admin.personnel.periodic-salary.show', $staff_id);
    }

    public function destroy(Request $request, $staff_id, $history_id)
    {
        try {
            $history = PeriodicSalaryHistory::where('staff_id', $staff_id)->findOrFail($history_id);

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
                            'text'  => 'Riwayat gaji berkala gagal dihapus. Silakan coba lagi.',
                        ],
                    ]));
            }

            return redirect()->route('admin.personnel.periodic-salary.show', $staff_id)
                ->with('error', 'Riwayat gaji berkala gagal dihapus.');
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
                        'text'  => 'Riwayat gaji berkala berhasil dihapus.',
                    ],
                ]));
        }

        return redirect()->route('admin.personnel.periodic-salary.show', $staff_id);
    }

    public function generatePdf(Request $request)
    {
        // Logika cetak PDF laporan KGB
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
        $unique = Rule::unique('staff_periodic_salary_histories')
            ->where(fn($query) => $query->where('staff_id', $staff->id));

        if ($ignoreId) {
            $unique->ignore($ignoreId);
        }

        return $request->validate([
            'effective_date' => ['required', 'date', $unique],
            'decree_number'  => ['nullable', 'string', 'max:255', 'required_with:file'],
            'decree_date'    => ['required', 'date'], // <-- Diubah menjadi required
            'base_salary'    => 'nullable|numeric',
            'file'           => ['nullable', 'file', 'mimes:pdf', 'max:' . StaffDocumentService::MAX_SIZE_KB],
        ], [
            'effective_date.required'     => 'TMT Berkala (Terhitung Mulai Tanggal) wajib diisi.', // <-- Tambahan pesan
            'effective_date.unique'       => 'Riwayat gaji berkala pada TMT (Tanggal) tersebut sudah ada untuk pegawai ini.',
            'decree_date.required'        => 'Tanggal SK wajib diisi.', // <-- Tambahan pesan
            'decree_number.required_with' => 'Nomor SK wajib diisi jika mengunggah berkas SK.',
            'file.mimes'                  => 'Berkas SK harus berformat PDF.',
            'file.max'                    => 'Ukuran berkas SK maksimal 1 MB.',
            'file.uploaded'               => 'Berkas SK gagal diunggah. Pastikan ukurannya maksimal 1 MB.',
        ]);
    }

    /** Query berkas SK berkala milik satu riwayat. */
    private function skDocuments(PeriodicSalaryHistory $history)
    {
        return $history->documents()
            ->whereHas('category', fn($c) => $c->where('code', self::SK_CATEGORY));
    }

    /**
     * Nomor & tanggal SK di berkas mengikuti data berkala (sumber kebenaran).
     * Hanya nilai yang terisi yang disalin, supaya nomor/tanggal pada berkas lama
     * tidak terhapus ketika data berkala-nya memang belum mencatat nomor SK.
     */
    private function syncSkMeta(PeriodicSalaryHistory $history): void
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
    private function storeSk(PeriodicSalaryHistory $history, Request $request): void
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
    protected function respondWithModalErrors(Request $request, ValidationException $e, Data $staff, ?PeriodicSalaryHistory $history)
    {
        if ($request->header('HX-Request')) {
            // Supaya old() bisa dipakai di blade untuk mempertahankan input user
            $request->flash();

            // @error() di blade memanggil $errors->getBag(), jadi harus ViewErrorBag
            // (bukan MessageBag mentah dari validator).
            $errorBag = new ViewErrorBag();
            $errorBag->put('default', $e->validator->errors());

            return response()
                ->view('pages.admin.career.salaries.show.partials._modal-form', [
                    'staff'   => $staff,
                    'history' => $history,
                    'errors'  => $errorBag,
                ], 200)
                ->header('HX-Retarget', '#modal-container')
                ->header('HX-Reswap', 'innerHTML');
        }

        throw $e;
    }
}
