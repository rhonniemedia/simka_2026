<?php

namespace Database\Seeders;

use App\Enums\Staff\DocumentGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sumber kebenaran master kategori dokumen. Idempoten: updateOrInsert berdasarkan `code`.
 * Format baris: [code, name, description, requires_number, requires_date, linked_table]
 */
class StaffDocumentCategorySeeder extends Seeder
{
    private const EDU = 'staff_education_histories';
    private const FAM = 'staff_family_relations';
    private const POS = 'staff_asn_positions_histories';
    private const SAL = 'staff_periodic_salary_histories';
    private const GRD = 'staff_grade_histories';
    private const STA = 'staff_status_histories';

    public function run(): void
    {
        $now = now();

        foreach (DocumentGroup::cases() as $group) {
            foreach (self::data()[$group->value] as $i => [$code, $name, $desc, $needNumber, $needDate, $linked]) {
                $values = [
                    'name'                     => $name,
                    'description'              => $desc,
                    'group_code'               => $group->value,
                    'requires_document_number' => $needNumber,
                    'requires_document_date'   => $needDate,
                    'linked_table'             => $linked,
                    'sort_order'               => $group->order() * 100 + ($i + 1) * 10,
                    'is_active'                => true,
                    'updated_at'               => $now,
                ];

                $exists = DB::table('staff_document_categories')->where('code', $code)->exists();

                if ($exists) {
                    DB::table('staff_document_categories')->where('code', $code)->update($values);
                } else {
                    DB::table('staff_document_categories')->insert($values + [
                        'id'         => (string) Str::uuid(),
                        'code'       => $code,
                        'created_at' => $now,
                    ]);
                }
            }
        }
    }

    private static function data(): array
    {
        return [
            'identity' => [
                ['ktp',            'Kartu Tanda Penduduk',   'KTP pegawai.', false, false, null],
                ['kk',             'Kartu Keluarga',         'Kartu Keluarga pegawai.', false, false, null],
                ['akta_kelahiran', 'Akta Kelahiran',         'Akta kelahiran pegawai sendiri.', false, false, null],
                ['kartu_npwp',     'Kartu NPWP',             'Nomor Pokok Wajib Pajak.', false, false, null],
                ['kartu_bpjs',     'Kartu BPJS',             'Kartu BPJS Kesehatan.', false, false, null],
                ['kartu_taspen',   'Kartu Taspen',          'Kartu peserta Taspen.', false, false, null],
                ['kartu_pegawai',  'Kartu Pegawai',          'Kartu identitas pegawai (Karpeg).', false, false, null],
                ['kartu_nrg',      'Kartu NRG',              'Nomor Registrasi Guru.', false, false, null],
                ['kartu_nuptk',    'Kartu NUPTK',            'Nomor Unik Pendidik dan Tenaga Kependidikan.', false, false, null],
                ['kartu_pgri',     'Kartu PGRI',             'Kartu anggota PGRI.', false, false, null],
            ],
            'appointment' => [
                ['sk_cpns',                  'SK CPNS',                       'SK pengangkatan calon PNS.', true, true, null],
                ['sk_pns',                   'SK PNS',                        'SK pengangkatan menjadi PNS.', true, true, null],
                ['sk_pppk',                  'SK Pengangkatan PPPK',          'SK pengangkatan PPPK.', true, true, null],
                ['perjanjian_kerja_pppk',    'Perjanjian Kerja PPPK',         'Perjanjian kerja PPPK (bukan SK).', true, true, null],
                ['sk_impasing',              'SK Impasing',                   'SK penyesuaian/impasing.', true, true, null],
                ['sk_peralihan',             'SK Peralihan',                  'SK peralihan status/jabatan.', true, true, null],
                ['sk_penyesuaian_masa_kerja', 'SK Penyesuaian Masa Kerja',     'SK penyesuaian masa kerja.', true, true, null],
                ['sk_nip_baru',              'SK Penetapan NIP Baru',         'SK penetapan NIP baru.', true, true, null],
                ['spmt',                     'Surat Pernyataan Melaksanakan Tugas (SPMT)', 'Surat pernyataan mulai melaksanakan tugas.', false, false, null],
            ],
            'status' => [
                ['sk_mutasi',            'SK Mutasi',            'SK mutasi/perpindahan.', true, true, self::STA],
                ['sk_pemberhentian',     'SK Pemberhentian',     'SK pemberhentian pegawai.', true, true, self::STA],
                ['sk_pengunduran_diri',  'SK Pengunduran Diri',  'SK pengunduran diri.', true, true, self::STA],
                ['sk_reaktivasi',        'SK Reaktivasi',        'SK pengaktifan kembali pegawai.', true, true, self::STA],
                ['surat_kematian',       'Surat Kematian',       'Surat kematian pegawai (status kepegawaian). Berbeda dengan Akta Kematian anggota keluarga.', true, false, self::STA],
            ],
            'position' => [
                ['sk_jabatan', 'SK Jabatan', 'SK pengangkatan/perubahan jabatan.', true, true, self::POS],
            ],
            'rank' => [
                ['sk_pangkat', 'SK Pangkat', 'SK kenaikan pangkat/golongan.', true, true, self::GRD],
            ],
            'salary' => [
                ['sk_berkala',    'SK Berkala',    'SK kenaikan gaji berkala.', true, true, self::SAL],
                ['buku_rekening', 'Buku Rekening', 'Halaman depan buku rekening gaji.', false, false, null],
            ],
            'education' => [
                ['ijazah',          'Ijazah',          'Ijazah; jenjang disimpan di riwayat pendidikan.', false, false, self::EDU],
                ['transkrip_nilai', 'Transkrip Nilai', 'Transkrip nilai; jenjang disimpan di riwayat pendidikan.', false, false, self::EDU],
            ],
            'family' => [
                ['akta_nikah',            'Akta Nikah',               'Akta/buku nikah.', false, false, self::FAM],
                ['akta_cerai',            'Akta Cerai',               'Akta perceraian.', false, false, self::FAM],
                ['akta_kematian',         'Akta Kematian',            'Akta kematian anggota keluarga.', false, false, self::FAM],
                ['akta_kelahiran_anak',   'Akta Kelahiran Anak',      'Akta kelahiran anak.', false, false, self::FAM],
                ['akta_kelahiran_pasangan', 'Akta Kelahiran Pasangan', 'Akta kelahiran suami/istri.', false, false, self::FAM],
                ['kartu_pasangan',        'Kartu Pasangan',           'Kartu identitas suami/istri (Karsu/Karis).', false, false, self::FAM],
            ],
            'competency' => [
                ['sertifikat_pendidik', 'Sertifikat Pendidik', 'Sertifikat pendidik/serdik.', false, false, null],
                ['akta_mengajar',       'Akta Mengajar',       'Akta mengajar.', false, false, null],
                ['sttpl',               'STTPL',               'Surat Tanda Tamat Pendidikan dan Latihan.', false, false, null],
                ['sertifikat_diklat',   'Sertifikat Diklat',   'Sertifikat pendidikan dan pelatihan.', false, false, null],
            ],
            'performance' => [
                ['skp', 'Sasaran Kinerja Pegawai (SKP)', 'Dokumen SKP tahunan.', false, false, null],
                ['pak', 'Penetapan Angka Kredit (PAK)',  'Penetapan angka kredit jabatan fungsional.', false, false, null],
            ],
            'retirement' => [
                // requires_document_number sengaja false (mengikuti data lama); ubah jika memang wajib.
                ['sk_pensiun', 'SK Pensiun', 'SK pemberhentian dengan hak pensiun.', false, true, self::STA],
            ],
        ];
    }
}
