<?php

namespace App\Observers;

use App\Models\EducationHistory;
use App\Models\Data; // Pastikan model Data (pegawai) di-import

class EducationHistoryObserver
{
    /**
     * Handle the EducationHistory "saved" event.
     * Berjalan ketika data baru ditambah atau di-update (termasuk saat status diverifikasi).
     */
    public function saved(EducationHistory $educationHistory): void
    {
        $this->generateAndSyncTitles($educationHistory->staff_id);
    }

    /**
     * Handle the EducationHistory "deleted" event.
     * Berjalan ketika riwayat pendidikan dihapus.
     */
    public function deleted(EducationHistory $educationHistory): void
    {
        $this->generateAndSyncTitles($educationHistory->staff_id);
    }

    /**
     * Logika utama untuk men-generate gelar.
     */
    private function generateAndSyncTitles(string $staffId): void
    {
        // 1. Ambil semua pendidikan yang 'verified' dan memiliki singkatan gelar
        $educations = EducationHistory::where('staff_id', $staffId)
            ->where('verification_status', 'verified')
            ->whereNotNull('degree_abbreviation')
            ->orderBy('graduation_date', 'asc') // Urutkan dari lulusan paling lama ke terbaru
            ->get();

        $frontTitles = [];
        $backTitles = [];

        // 2. Pisahkan gelar depan dan belakang
        foreach ($educations as $edu) {
            $degree = trim($edu->degree_abbreviation);

            if ($edu->degree_position === 'depan') {
                // Untuk gelar depan, gelar tertinggi biasanya ditaruh paling depan (misal: Prof. Dr. Ir.)
                // array_unshift memasukkan gelar baru ke posisi paling depan array
                array_unshift($frontTitles, $degree);
            } elseif ($edu->degree_position === 'belakang') {
                // Gelar belakang berurutan S1, S2, dst.
                $backTitles[] = $degree;
            }
        }

        // 3. Rangkai menjadi string
        // Gelar depan dipisah spasi (contoh: "Dr. Ir.")
        $frontString = !empty($frontTitles) ? implode(' ', $frontTitles) : null;

        // Gelar belakang dipisah koma dan spasi (contoh: "S.Kom., M.T.")
        $backString = !empty($backTitles) ? implode(', ', $backTitles) : null;

        // 4. Simpan hasilnya ke tabel staff_data
        Data::where('id', $staffId)->update([
            'front_title' => $frontString,
            'back_title'  => $backString
        ]);
    }
}
