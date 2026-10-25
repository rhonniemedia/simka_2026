<?php

namespace App\Services\Staff;

use App\Models\Data;
use App\Models\Document;
use App\Models\DocumentCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Satu pintu untuk menyimpan berkas pegawai, baik dari form tiap menu
 * (Pangkat, Berkala, Jabatan, ...) maupun dari menu Dokumen.
 *
 * Aturan yang dijaga di sini:
 *  - kategori harus aktif
 *  - kategori bertaut (linked_table terisi) WAJIB ditautkan ke data yang
 *    jenisnya sesuai dan milik pegawai yang sama
 *  - kategori mandiri (linked_table kosong) tidak boleh punya tautan
 *  - nomor dokumen wajib jika kategori mensyaratkan
 *
 * Pemeriksaan isi file (mime sebenarnya) dilakukan di Form Request memakai
 * aturan `File::types([...])`; di sini hanya pemeriksaan dasar.
 */
class StaffDocumentService
{
    /** Disk khusus berkas (storage/app/private/documents). Berkas lama juga di sini. */
    public const DISK = 'documents';

    public const ALLOWED_EXTENSIONS = ['pdf'];

    public const MAX_SIZE_KB = 1024; // 1 MB

    /**
     * @param  Model|null  $linked  Baris riwayat yang ditautkan (abaikan untuk kategori mandiri)
     * @param  array       $meta    document_number, document_date, document_name, verification_status
     */
    public function store(
        Data $staff,
        DocumentCategory $category,
        UploadedFile $file,
        ?Model $linked = null,
        array $meta = []
    ): Document {
        $this->assertCategoryUsable($category);
        $this->assertFile($file);
        $linked = $this->resolveLink($staff, $category, $linked);

        $number = $meta['document_number'] ?? $linked?->getAttribute('decree_number');
        $date   = $meta['document_date'] ?? $linked?->getAttribute('decree_date');

        if ($category->requires_document_number && blank($number)) {
            throw ValidationException::withMessages([
                'document_number' => 'Nomor dokumen wajib diisi untuk kategori ' . $category->name . '.',
            ]);
        }

        $path = $file->store("staff/{$staff->id}/{$category->code}", self::DISK);

        if ($path === false) {
            throw ValidationException::withMessages(['file' => 'Berkas gagal disimpan, silakan coba lagi.']);
        }

        try {
            return Document::create([
                'staff_id'                   => $staff->id,
                'staff_document_category_id' => $category->id,
                'document_number'            => $number,
                'document_date'              => $date,
                'linked_record_type'         => $linked?->getMorphClass(),
                'linked_record_id'           => $linked?->getKey(),
                'document_name'              => $meta['document_name'] ?? $category->name,
                'file_path'                  => $path,
                'original_filename'          => Str::limit($file->getClientOriginalName(), 255, ''),
                'mime_type'                  => $file->getMimeType(),
                'file_size'                  => $file->getSize(),
                'verification_status'        => $meta['verification_status'] ?? 'draft',
                'created_by'                 => Auth::id(),
            ]);
        } catch (Throwable $e) {
            // Jangan tinggalkan file yatim jika baris database gagal dibuat
            Storage::disk(self::DISK)->delete($path);

            throw $e;
        }
    }

    private function assertCategoryUsable(DocumentCategory $category): void
    {
        if (! $category->is_active) {
            throw ValidationException::withMessages([
                'category' => 'Kategori dokumen ' . $category->name . ' sudah tidak aktif.',
            ]);
        }
    }

    private function assertFile(UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw ValidationException::withMessages(['file' => 'Berkas gagal diunggah.']);
        }

        if (! in_array(strtolower($file->getClientOriginalExtension()), self::ALLOWED_EXTENSIONS, true)) {
            throw ValidationException::withMessages([
                'file' => 'Format berkas harus ' . strtoupper(implode(', ', self::ALLOWED_EXTENSIONS)) . '.',
            ]);
        }

        if ($file->getSize() > self::MAX_SIZE_KB * 1024) {
            throw ValidationException::withMessages([
                'file' => 'Ukuran berkas maksimal ' . (self::MAX_SIZE_KB / 1024) . ' MB.',
            ]);
        }
    }

    /**
     * Kembalikan model tujuan tautan, atau null untuk kategori mandiri.
     */
    private function resolveLink(Data $staff, DocumentCategory $category, ?Model $linked): ?Model
    {
        // Kategori mandiri: tautan yang dikirim diabaikan
        if (blank($category->linked_table)) {
            return null;
        }

        if (! $linked) {
            throw ValidationException::withMessages([
                'linked_record_id' => 'Dokumen ' . $category->name . ' harus ditautkan ke data terkait.',
            ]);
        }

        // Alias morph = nama tabel, jadi bisa dibandingkan langsung dengan linked_table
        if ($linked->getMorphClass() !== $category->linked_table) {
            throw ValidationException::withMessages([
                'linked_record_id' => 'Data terkait tidak sesuai dengan jenis dokumen ' . $category->name . '.',
            ]);
        }

        if ((string) $linked->getAttribute('staff_id') !== (string) $staff->id) {
            throw ValidationException::withMessages([
                'linked_record_id' => 'Data terkait bukan milik pegawai ini.',
            ]);
        }

        return $linked;
    }
}
