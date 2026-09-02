<?php

namespace App\Http\Controllers\Admin\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Data;
use App\Models\PeriodicSalaryHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PeriodicSalaryHistoryController extends Controller
{
    public function index(Request $request)
    {
        // Mengambil data gaji berkala beserta data pegawai terkait
        $histories = PeriodicSalaryHistory::with(['staff.vault'])
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
            return view('pages.admin.personnel.periodic-salary.partials._table', compact('histories'));
        }

        return view('pages.admin.personnel.periodic-salary.index', compact('histories'));
    }

    public function show(Request $request, $id)
    {
        $staff = Data::with(['vault', 'employmentStatus'])->findOrFail($id);

        $histories = PeriodicSalaryHistory::where('staff_id', $id)
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
            return view('pages.admin.personnel.periodic-salary.show.partials._table', compact('staff', 'histories'));
        }

        return view('pages.admin.personnel.periodic-salary.show.index', compact('staff', 'histories'));
    }

    public function create($id)
    {
        $staff = Data::findOrFail($id);

        // FIX: sebelumnya menunjuk ke 'modals._modal-form' (path yang tidak ada / salah),
        // padahal file aslinya ada di 'show/partials/_modal-form.blade.php'.
        return view('pages.admin.personnel.periodic-salary.show.partials._modal-form', compact('staff'));
    }

    public function store(Request $request, $id)
    {
        $staff = Data::findOrFail($id);

        try {
            $validated = $request->validate([
                'effective_date' => [
                    'required',
                    'date',
                    Rule::unique('staff_periodic_salary_histories')->where(function ($query) use ($staff) {
                        return $query->where('staff_id', $staff->id);
                    })
                ],
                'decree_number' => 'nullable|string|max:255',
                'decree_date'   => 'nullable|date',
                'base_salary'   => 'nullable|numeric',
            ], [
                'effective_date.unique' => 'Riwayat gaji berkala pada TMT (Tanggal) tersebut sudah ada untuk pegawai ini.'
            ]);
        } catch (ValidationException $e) {
            return $this->respondWithModalErrors($request, $e, $staff, null);
        }

        // Model akan otomatis mendeteksi array key 'base_salary',
        // mengenkripsinya, dan mengubahnya menjadi 'base_salary_encrypted'.
        $staff->periodicSalaries()->create($validated);

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

        // FIX: sama seperti create(), path view diperbaiki.
        return view('pages.admin.personnel.periodic-salary.show.partials._modal-form', compact('staff', 'history'));
    }

    public function update(Request $request, $staff_id, $history_id)
    {
        $staff = Data::findOrFail($staff_id);
        $history = PeriodicSalaryHistory::where('staff_id', $staff_id)->findOrFail($history_id);

        try {
            $validated = $request->validate([
                'effective_date' => [
                    'required',
                    'date',
                    Rule::unique('staff_periodic_salary_histories')->where(function ($query) use ($staff) {
                        return $query->where('staff_id', $staff->id);
                    })->ignore($history_id)
                ],
                'decree_number' => 'nullable|string|max:255',
                'decree_date'   => 'nullable|date',
                'base_salary'   => 'nullable|numeric',
            ], [
                'effective_date.unique' => 'Riwayat gaji berkala pada TMT (Tanggal) tersebut sudah ada untuk pegawai ini.'
            ]);
        } catch (ValidationException $e) {
            return $this->respondWithModalErrors($request, $e, $staff, $history);
        }

        $history->update($validated);

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

            return response()
                ->view('pages.admin.personnel.periodic-salary.show.partials._modal-form', [
                    'staff'   => $staff,
                    'history' => $history,
                    'errors'  => $e->validator->errors(),
                ], 200)
                ->header('HX-Retarget', '#modal-container')
                ->header('HX-Reswap', 'innerHTML');
        }

        throw $e;
    }
}
