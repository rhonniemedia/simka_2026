<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Data;
use App\Models\EducationHistory;

class SyncStaffTitles extends Command
{
    /**
     * Nama perintah yang akan diketik di terminal.
     */
    protected $signature = 'staff:sync-titles';

    /**
     * Deskripsi singkat tentang command ini.
     */
    protected $description = 'Men-generate dan menyinkronkan ulang gelar depan dan belakang seluruh pegawai berdasarkan riwayat pendidikan.';

    /**
     * Eksekusi command.
     */
    public function handle()
    {
        $this->info('Memulai sinkronisasi gelar pegawai...');

        // Ambil seluruh data pegawai
        $staffs = Data::all();

        if ($staffs->isEmpty()) {
            $this->warn('Tidak ada data pegawai ditemukan.');
            return;
        }

        // Membuat progress bar di terminal
        $bar = $this->output->createProgressBar($staffs->count());
        $bar->start();

        foreach ($staffs as $staff) {
            $this->generateAndSyncTitles($staff->id);
            $bar->advance();
        }

        $bar->finish();

        $this->newLine(2);
        $this->info('Sinkronisasi gelar untuk ' . $staffs->count() . ' pegawai telah selesai!');
    }

    /**
     * Logika generator (sama persis dengan yang ada di Observer).
     */
    private function generateAndSyncTitles(string $staffId): void
    {
        $educations = EducationHistory::where('staff_id', $staffId)
            ->where('verification_status', 'verified')
            ->whereNotNull('degree_abbreviation')
            ->orderBy('graduation_date', 'asc') // Urutkan dari lulusan paling lama ke terbaru
            ->get();

        $frontTitles = [];
        $backTitles = [];

        foreach ($educations as $edu) {
            $degree = trim($edu->degree_abbreviation);

            if ($edu->degree_position === 'depan') {
                array_unshift($frontTitles, $degree);
            } elseif ($edu->degree_position === 'belakang') {
                $backTitles[] = $degree;
            }
        }

        $frontString = !empty($frontTitles) ? implode(' ', $frontTitles) : null;
        $backString = !empty($backTitles) ? implode(', ', $backTitles) : null;

        Data::where('id', $staffId)->update([
            'front_title' => $frontString,
            'back_title'  => $backString
        ]);
    }
}
