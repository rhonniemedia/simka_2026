<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Migrasi data staff_families (lama, 1 baris = 1 relasi + data orang
     * digandakan) menjadi:
     * - staff_family_members : 1 baris per ORANG unik (dedup via NIK)
     * - staff_family_relations : 1 baris per RELASI staff<->orang
     *
     * Tabel lama TIDAK dihapus, hanya di-rename jadi staff_families_legacy
     * agar bisa di-rollback dengan aman tanpa kehilangan data apapun.
     */
    public function up(): void
    {
        // trimmed NIK plaintext => id baris baru di staff_family_members
        $nikMap = [];

        DB::table('staff_families')
            ->orderBy('created_at')
            ->orderBy('id')
            ->chunk(200, function ($rows) use (&$nikMap) {
                foreach ($rows as $row) {
                    // nik & birth_date & telephone di-set lewat mutator kustom
                    // di model Family (pakai fungsi global encrypt()/decrypt()).
                    $nikPlain = $this->safeDecrypt($row->nik_encrypted, 'legacy', $row->id, 'nik_encrypted');
                    $nikPlain = $nikPlain !== null ? trim($nikPlain) : null;

                    $birthDatePlain = $this->safeDecrypt($row->birth_date_encrypted, 'legacy', $row->id, 'birth_date_encrypted');
                    $telephonePlain = $this->safeDecrypt($row->telephone_encrypted, 'legacy', $row->id, 'telephone_encrypted');

                    // birth_place & marriage_date pakai cast bawaan Laravel 'encrypted'
                    // (Crypt::encryptString / decryptString), tidak lewat mutator kustom.
                    $birthPlacePlain = $this->safeDecrypt($row->birth_place_encrypted, 'string', $row->id, 'birth_place_encrypted');
                    $marriageDatePlain = $this->safeDecrypt($row->marriage_date_encrypted, 'string', $row->id, 'marriage_date_encrypted');

                    $familyMemberId = ($nikPlain !== null && $nikPlain !== '')
                        ? ($nikMap[$nikPlain] ?? null)
                        : null;

                    if ($familyMemberId === null) {
                        $familyMemberId = (string) Str::uuid();

                        DB::table('staff_family_members')->insert([
                            'id'                    => $familyMemberId,
                            'name'                  => $row->name,
                            'gender'                => $row->gender,
                            'nik_encrypted'         => $nikPlain ? Crypt::encryptString($nikPlain) : null,
                            'nik_hash'              => $nikPlain ? hash('sha256', $nikPlain) : null,
                            'birth_place_encrypted' => $birthPlacePlain ? Crypt::encryptString($birthPlacePlain) : null,
                            'birth_date_encrypted'  => $birthDatePlain ? Crypt::encryptString($birthDatePlain) : null,
                            'birth_date_hash'       => $birthDatePlain ? hash('sha256', trim($birthDatePlain)) : null,
                            'telephone_encrypted'   => $telephonePlain ? Crypt::encryptString($telephonePlain) : null,
                            'telephone_hash'        => $telephonePlain ? hash('sha256', trim($telephonePlain)) : null,
                            'occupation'            => $row->occupation,
                            'education_level_id'    => $row->education_level_id,
                            'is_studying'           => $row->is_studying,
                            'linked_staff_id'       => null, // diisi tahap 2 di bawah
                            'verification_status'   => $row->verification_status ?? 'draft',
                            'created_by'            => $row->created_by,
                            'updated_by'            => $row->updated_by,
                            'legacy_id'             => $row->id,
                            'created_at'            => $row->created_at,
                            'updated_at'            => $row->updated_at,
                        ]);

                        if ($nikPlain !== null && $nikPlain !== '') {
                            $nikMap[$nikPlain] = $familyMemberId;
                        }
                    }

                    DB::table('staff_family_relations')->insert([
                        'id'                      => (string) Str::uuid(),
                        'staff_id'                => $row->staff_id,
                        'family_member_id'        => $familyMemberId,
                        'relationship'            => $row->relationship,
                        'family_relation_code'    => $row->family_relation_code,
                        'payroll_status'          => $row->payroll_status,
                        'marriage_date_encrypted' => $marriageDatePlain ? Crypt::encryptString($marriageDatePlain) : null,
                        'verification_status'     => $row->verification_status ?? 'draft',
                        'created_by'              => $row->created_by,
                        'updated_by'              => $row->updated_by,
                        'legacy_id'               => $row->id,
                        'created_at'              => $row->created_at,
                        'updated_at'              => $row->updated_at,
                    ]);
                }
            });

        // Tahap 2: tandai anggota keluarga yang NIK-nya cocok dengan
        // seorang staff (staff_data_vault) -> kemungkinan pasangan
        // suami-istri yang sama-sama pegawai.
        DB::table('staff_family_members')
            ->whereNotNull('nik_hash')
            ->orderBy('id')
            ->chunk(200, function ($members) {
                foreach ($members as $member) {
                    $matchedStaffId = DB::table('staff_data_vault')
                        ->where('nik_hash', $member->nik_hash)
                        ->value('staff_id');

                    if ($matchedStaffId) {
                        DB::table('staff_family_members')
                            ->where('id', $member->id)
                            ->update(['linked_staff_id' => $matchedStaffId]);
                    }
                }
            });

        // Tahap 3: arsipkan tabel lama. Tidak di-drop supaya bisa
        // di-rollback / diaudit kapan saja tanpa kehilangan data asli.
        Schema::rename('staff_families', 'staff_families_legacy');
    }

    public function down(): void
    {
        if (Schema::hasTable('staff_families_legacy') && !Schema::hasTable('staff_families')) {
            Schema::rename('staff_families_legacy', 'staff_families');
        }

        // Matikan pengecekan foreign key sementara
        Schema::disableForeignKeyConstraints();

        DB::table('staff_family_relations')->truncate();
        DB::table('staff_family_members')->truncate();

        // Hidupkan kembali pengecekan foreign key
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Decrypt nilai lama secara aman. Kalau gagal (data korup/format lama),
     * dicatat ke log dan baris tetap lanjut diproses dengan nilai null
     * untuk field tersebut - tidak menghentikan seluruh migrasi.
     */
    private function safeDecrypt(?string $value, string $mode, string $rowId, string $field): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return $mode === 'legacy' ? decrypt($value) : Crypt::decryptString($value);
        } catch (\Throwable $e) {
            // Tangkap semua jenis error, termasuk ErrorException dari unserialize() PHP
            if ($mode === 'legacy' && str_contains($e->getMessage(), 'unserialize')) {
                try {
                    // Fallback: Decrypt sebagai string biasa tanpa unserialize
                    return Crypt::decryptString($value);
                } catch (\Throwable $e2) {
                    // Jika masih gagal, biarkan turun ke pencatatan log di bawah
                }
            }

            Log::warning('Gagal decrypt kolom saat migrasi staff_families', [
                'row_id' => $rowId,
                'field'  => $field,
                'mode'   => $mode,
                'error'  => $e->getMessage(),
            ]);

            return null;
        }
    }
};
