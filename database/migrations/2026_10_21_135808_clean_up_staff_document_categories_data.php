<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * TAHAP 2: Rapikan data kategori dokumen.
 *
 * Prasyarat: migrasi Tahap 1 sudah dijalankan (kolom `code` ada dan
 * kolom `group` sudah berupa string, bukan enum database).
 *
 * Migrasi ini memakai DB::table() (bukan model) supaya tidak bergantung
 * pada perubahan model/enum di masa depan. Aman dijalankan ulang
 * (idempoten): pencocokan memakai name/code, bukan id.
 */
return new class extends Migration
{
    private const CATEGORIES = 'staff_document_categories';
    private const DOCUMENTS  = 'staff_documents';

    public function up(): void
    {
        DB::transaction(function () {
            $now = now();

            $this->renameCategories($now);
            $this->fillCodesAndLinks($now);
            $this->createNewCategories($now);
            $this->mergeCategories($now);
        });
    }

    public function down(): void
    {
        // Migrasi data ini tidak dibuat bisa di-rollback secara otomatis
        // (penggabungan kategori memindahkan berkas). Gunakan backup
        // database yang dibuat sebelum menjalankannya.
    }

    /* ------------------------------------------------------------------
     | A. Perbaikan nama
     |------------------------------------------------------------------ */
    private function renameCategories(Carbon $now): void
    {
        $renames = [
            'Kartu Kaluarga' => 'Kartu Keluarga',   // salah ketik
            'SK Pindah'      => 'SK Mutasi',
        ];

        foreach ($renames as $old => $new) {
            DB::table(self::CATEGORIES)
                ->where('name', $old)
                ->update(['name' => $new, 'updated_at' => $now]);
        }
    }

    /* ------------------------------------------------------------------
     | B. Isi code, group (jika berubah), linked_table untuk 47 baris lama
     |    Kunci array = nama kategori SETELAH rename.
     |    Opsi: code (wajib), group, linked, requires
     |------------------------------------------------------------------ */
    private function fillCodesAndLinks(Carbon $now): void
    {
        $map = [
            // ---- Mandiri (linked_table = null) ----
            'SK CPNS'                                    => ['code' => 'sk_cpns'],
            'SK PNS'                                     => ['code' => 'sk_pns'],
            'SK Impasing'                                => ['code' => 'sk_impasing'],
            'SK Peralihan'                               => ['code' => 'sk_peralihan'],
            'SK Penyesuaian Masa Kerja'                  => ['code' => 'sk_penyesuaian_masa_kerja'],
            'SK Penetapan NIP Baru'                      => ['code' => 'sk_nip_baru'],
            'Surat Pernyataan Melaksanakan Tugas (SPMT)' => ['code' => 'spmt'],
            'Sasaran Kinerja Pegawai (SKP)'              => ['code' => 'skp'],
            'Akta Kelahiran'                             => ['code' => 'akta_kelahiran'],
            'Akta Mengajar'                              => ['code' => 'akta_mengajar', 'group' => 'sertifikat'],
            'Sertifikat Pendidik'                        => ['code' => 'sertifikat_pendidik'],
            'Sertifikat Diklat'                          => ['code' => 'sertifikat_diklat'],
            'STTPL'                                      => ['code' => 'sttpl'],
            'Kartu Tanda Penduduk'                       => ['code' => 'ktp'],
            'Kartu Keluarga'                             => ['code' => 'kk'],
            'Kartu NPWP'                                 => ['code' => 'kartu_npwp'],
            'Kartu Pegawai'                              => ['code' => 'kartu_pegawai'],
            'Kartu NRG'                                  => ['code' => 'kartu_nrg'],
            'Kartu NUPTK'                                => ['code' => 'kartu_nuptk'],
            'Kartu BPJS'                                 => ['code' => 'kartu_bpjs'],
            'Kartu PGRI'                                 => ['code' => 'kartu_pgri'],
            'Kartu Taspen'                               => ['code' => 'kartu_taspen'],
            'Buku Rekening'                              => ['code' => 'buku_rekening'],

            // ---- Bertaut ke data riwayat ----
            'SK Pangkat'      => ['code' => 'sk_pangkat', 'linked' => 'staff_grade_history'],
            'SK Berkala'      => ['code' => 'sk_berkala', 'linked' => 'staff_periodic_salary_history'],
            'SK Jabatan'      => ['code' => 'sk_jabatan', 'group' => 'sk_fungsional', 'linked' => 'staff_asn_position_history'],
            'SK Mutasi'       => ['code' => 'sk_mutasi', 'group' => 'sk_mutasi', 'linked' => 'staff_status_history'],
            'Transkrip Nilai' => ['code' => 'transkrip_nilai', 'linked' => 'staff_education'],

            'Akta Kelahiran Anak' => ['code' => 'akta_kelahiran_anak', 'linked' => 'staff_family'],
            'Akta Nikah'          => ['code' => 'akta_nikah', 'linked' => 'staff_family'],
            'Akta Cerai'          => ['code' => 'akta_cerai', 'linked' => 'staff_family'],
            'Akta Kematian'       => ['code' => 'akta_kematian', 'linked' => 'staff_family'],
            // Disamakan dengan Kartu Istri (sebelumnya Suami = wajib nomor, Istri = tidak).
            // Hapus 'requires' di bawah jika ingin mempertahankan nilai lama.
            'Kartu Suami'         => ['code' => 'kartu_suami', 'linked' => 'staff_family', 'requires' => false],
            'Kartu Istri'         => ['code' => 'kartu_istri', 'linked' => 'staff_family'],

            // ---- Kategori lama yang akan digabung (dinonaktifkan di langkah D) ----
            'Ijazah TK'     => ['code' => 'ijazah_tk'],
            'Ijazah SD'     => ['code' => 'ijazah_sd'],
            'Ijazah SMP'    => ['code' => 'ijazah_smp'],
            'Ijazah SMA/SMK' => ['code' => 'ijazah_sma'],
            'Ijazah D1'     => ['code' => 'ijazah_d1'],
            'Ijazah D2'     => ['code' => 'ijazah_d2'],
            'Ijazah D3'     => ['code' => 'ijazah_d3'],
            'Ijazah S1/D4'  => ['code' => 'ijazah_s1'],
            'Ijazah S2'     => ['code' => 'ijazah_s2'],
            'Ijazah S3'     => ['code' => 'ijazah_s3'],
            'Akta Kelahiran Suami' => ['code' => 'akta_kelahiran_suami'],
            'Akta Kelahiran Istri' => ['code' => 'akta_kelahiran_istri'],
            'SK Fungsional'        => ['code' => 'sk_fungsional'],
        ];

        foreach ($map as $name => $opt) {
            $row = DB::table(self::CATEGORIES)->where('name', $name)->first();

            if (! $row) {
                Log::warning("[Tahap 2] Kategori tidak ditemukan, dilewati: {$name}");
                continue;
            }

            $update = [
                'code'         => $opt['code'],
                'linked_table' => $opt['linked'] ?? null,
                'updated_at'   => $now,
            ];

            if (isset($opt['group'])) {
                $update['group'] = $opt['group'];
            }
            if (array_key_exists('requires', $opt)) {
                $update['requires_document_number'] = $opt['requires'];
            }

            DB::table(self::CATEGORIES)->where('id', $row->id)->update($update);
        }
    }

    /* ------------------------------------------------------------------
     | C. Kategori baru (dilewati jika code sudah ada)
     |------------------------------------------------------------------ */
    private function createNewCategories(Carbon $now): void
    {
        $new = [
            ['code' => 'ijazah', 'name' => 'Ijazah', 'group' => 'ijazah', 'linked' => 'staff_education', 'requires' => false],
            ['code' => 'akta_kelahiran_pasangan', 'name' => 'Akta Kelahiran Pasangan', 'group' => 'akta', 'linked' => 'staff_family', 'requires' => false],
            ['code' => 'sk_pensiun', 'name' => 'SK Pensiun', 'group' => 'sk_pensiun', 'linked' => 'staff_status_history', 'requires' => true],
            ['code' => 'pak', 'name' => 'Penetapan Angka Kredit (PAK)', 'group' => 'pak', 'linked' => null, 'requires' => false],
            ['code' => 'sk_pppk', 'name' => 'SK Pengangkatan PPPK', 'group' => 'sk_pengangkatan', 'linked' => null, 'requires' => true],
            ['code' => 'perjanjian_kerja_pppk', 'name' => 'Perjanjian Kerja PPPK', 'group' => 'sk_pengangkatan', 'linked' => null, 'requires' => true],
        ];

        foreach ($new as $c) {
            if (DB::table(self::CATEGORIES)->where('code', $c['code'])->exists()) {
                continue;
            }

            DB::table(self::CATEGORIES)->insert([
                'id'                       => (string) Str::uuid(),
                'name'                     => $c['name'],
                'code'                     => $c['code'],
                'group'                    => $c['group'],
                'requires_document_number' => $c['requires'],
                'linked_table'             => $c['linked'],
                'is_active'                => true,
                'created_at'               => $now,
                'updated_at'               => $now,
            ]);
        }
    }

    /* ------------------------------------------------------------------
     | D. Gabung kategori: pindahkan berkas lama, lalu nonaktifkan kategori lama
     |    (kategori tidak dihapus karena staff_documents punya FK ke sini)
     |------------------------------------------------------------------ */
    private function mergeCategories(Carbon $now): void
    {
        $merge = [
            // code tujuan => [code kategori lama]
            'ijazah' => [
                'ijazah_tk',
                'ijazah_sd',
                'ijazah_smp',
                'ijazah_sma',
                'ijazah_d1',
                'ijazah_d2',
                'ijazah_d3',
                'ijazah_s1',
                'ijazah_s2',
                'ijazah_s3',
            ],
            'akta_kelahiran_pasangan' => ['akta_kelahiran_suami', 'akta_kelahiran_istri'],
            'sk_jabatan'              => ['sk_fungsional'],
        ];

        foreach ($merge as $targetCode => $sourceCodes) {
            $targetId = DB::table(self::CATEGORIES)->where('code', $targetCode)->value('id');

            if (! $targetId) {
                Log::warning("[Tahap 2] Kategori tujuan tidak ditemukan: {$targetCode}");
                continue;
            }

            $sources = DB::table(self::CATEGORIES)
                ->whereIn('code', $sourceCodes)
                ->get(['id', 'name']);

            foreach ($sources as $src) {
                // Jaga informasi jenjang/jenis: jika nama berkas kosong,
                // isi dengan nama kategori lama (mis. "Ijazah SD").
                // DB::table() ikut memproses berkas yang sudah di-soft-delete.
                DB::table(self::DOCUMENTS)
                    ->where('staff_document_category_id', $src->id)
                    ->where('document_name', '')
                    ->update(['document_name' => $src->name]);

                DB::table(self::DOCUMENTS)
                    ->where('staff_document_category_id', $src->id)
                    ->update(['staff_document_category_id' => $targetId]);

                DB::table(self::CATEGORIES)
                    ->where('id', $src->id)
                    ->update(['is_active' => false, 'updated_at' => $now]);
            }
        }
    }
};
