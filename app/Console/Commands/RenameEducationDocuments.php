<?php

namespace App\Console\Commands;

use App\Models\EducationHistory;
use Illuminate\Console\Command;

/**
 * Isi ulang nama berkas Ijazah & Transkrip Nilai yang sudah ada dengan
 * rumus "{Kategori} {ALIAS jenjang}" (mis. "Ijazah S1"), karena data lama
 * semuanya bernama sama ("Ijazah" / "Transkrip Nilai").
 *
 * Jalankan dulu simulasi:   php artisan staff:rename-education-documents --dry-run
 * Lalu jalankan sungguhan:  php artisan staff:rename-education-documents
 */
class RenameEducationDocuments extends Command
{
    protected $signature = 'staff:rename-education-documents {--dry-run : Hanya tampilkan perubahan, tidak menyimpan}';

    protected $description = 'Perbarui document_name berkas Ijazah & Transkrip Nilai sesuai jenjang pendidikan';

    public function handle(): int
    {
        $dryRun  = (bool) $this->option('dry-run');
        $codes   = ['ijazah', 'transkrip_nilai'];
        $changed = 0;

        EducationHistory::query()->chunkById(200, function ($educations) use ($codes, $dryRun, &$changed) {
            foreach ($educations as $education) {
                $documents = $education->documents()
                    ->with('category')
                    ->get()
                    ->filter(fn($doc) => in_array($doc->category?->code, $codes, true));

                foreach ($documents as $doc) {
                    $newName = $education->documentName($doc->category->code);

                    if ($doc->document_name === $newName) {
                        continue;
                    }

                    $this->line("[{$education->id}] {$doc->document_name}  ->  {$newName}");

                    if (! $dryRun) {
                        $doc->update(['document_name' => $newName]);
                    }

                    $changed++;
                }
            }
        });

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "{$changed} berkas " . ($dryRun ? 'akan diubah.' : 'diubah.'));

        return self::SUCCESS;
    }
}
