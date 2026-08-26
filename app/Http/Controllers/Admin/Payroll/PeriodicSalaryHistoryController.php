<?php

namespace App\Http\Controllers\Admin\Payroll;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\PeriodicSalaryHistory;
use Illuminate\Http\Request;

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
                    // Gunakan null, jangan '-' untuk data tanggal
                    $history->tmt_berikut = null;
                    $history->tahun_ke = null;
                }
            } else {
                $history->masa_kerja = '-';
                // Gunakan null, jangan '-' untuk data tanggal
                $history->tmt_berikut = null;
                $history->tahun_ke = '-';
            }

            return $history;
        });

        // Jika request dari HTMX, kembalikan partial table saja
        if ($request->header('HX-Request')) {
            return view('pages.admin.personnel.periodic-salary.partials._table', compact('histories'));
        }

        // Jika full page load
        return view('pages.admin.personnel.periodic-salary.index', compact('histories'));
    }

    public function create()
    {
        // Tampilkan form tambah (atau kembalikan ke modal)
    }

    public function store(Request $request)
    {
        // Validasi dan simpan data baru
    }

    public function show($id)
    {
        // Tampilkan detail riwayat berkala spesifik
    }

    public function edit($id)
    {
        // Ambil data untuk form edit
    }

    public function update(Request $request, $id)
    {
        // Validasi dan perbarui data yang berubah
    }

    public function destroy($id)
    {
        // Hapus data riwayat berkala
    }

    public function generatePdf(Request $request)
    {
        // Logika cetak PDF laporan KGB
    }
}
