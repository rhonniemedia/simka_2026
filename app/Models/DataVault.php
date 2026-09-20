<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class DataVault extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'staff_data_vault';

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = ['id'];

    // Sembunyikan semua field terenkripsi saat model di-return sebagai JSON/Array
    protected $hidden = [
        'nik_encrypted',
        'nip_encrypted',
        'nuptk_encrypted',
        'pob_encrypted',
        'dob_encrypted',
        'religion_encrypted',
        'npwp_encrypted',
        'bank_account_encrypted',
        'base_salary_encrypted',
        'phone_number_encrypted',
        'email_encrypted',
        'address_encrypted',
        'rt_encrypted',
        'rw_encrypted',
        'village_encrypted',
        'district_encrypted',
        'regency_encrypted',
        'province_encrypted',
    ];

    // ========================================================================
    // HELPER ENKRIPSI
    // ========================================================================

    /**
     * Membuat accessor/mutator untuk field yang disimpan terenkripsi di kolom
     * "{$name}_encrypted", dengan kolom "{$name}_hash" opsional.
     *
     * - get : mendekripsi kolom terenkripsi (null bila kosong).
     * - set : trim lalu enkripsi; nilai kosong (null / '' / spasi saja) disimpan
     *         sebagai null. Nilai "0" tetap dianggap terisi.
     *
     * @param string $name       Nama atribut (snake_case), mis. 'phone_number'.
     * @param bool   $withHash   Isi juga kolom "{$name}_hash" (SHA-256) untuk pencarian/unik.
     * @param bool   $lowerHash  Hash dihitung dari versi lowercase, supaya "Islam" dan
     *                           "islam" menghasilkan hash yang sama. Nilai terenkripsi
     *                           tetap menyimpan teks aslinya.
     */
    private function encryptedAttribute(string $name, bool $withHash = false, bool $lowerHash = false): Attribute
    {
        $encryptedColumn = "{$name}_encrypted";
        $hashColumn      = "{$name}_hash";

        return Attribute::make(
            get: fn($value, $attributes) => filled($attributes[$encryptedColumn] ?? null)
                ? Crypt::decryptString($attributes[$encryptedColumn])
                : null,
            set: function ($value) use ($encryptedColumn, $hashColumn, $withHash, $lowerHash) {
                // trim() membuang spasi yang tidak sengaja terinput.
                $clean = filled($value) ? trim((string) $value) : '';
                $clean = $clean !== '' ? $clean : null;

                $result = [
                    $encryptedColumn => $clean !== null ? Crypt::encryptString($clean) : null,
                ];

                if ($withHash) {
                    // strtolower (bukan mb_strtolower) dipertahankan agar hash data
                    // lama tetap sama.
                    $result[$hashColumn] = $clean !== null
                        ? hash('sha256', $lowerHash ? strtolower($clean) : $clean)
                        : null;
                }

                return $result;
            }
        );
    }

    // ========================================================================
    // IDENTITAS
    // ========================================================================

    protected function nik(): Attribute
    {
        return $this->encryptedAttribute('nik', withHash: true);
    }

    protected function nip(): Attribute
    {
        return $this->encryptedAttribute('nip', withHash: true);
    }

    protected function nuptk(): Attribute
    {
        return $this->encryptedAttribute('nuptk', withHash: true);
    }

    // Tempat Lahir (hanya enkripsi, tidak ada hash di migration)
    protected function pob(): Attribute
    {
        return $this->encryptedAttribute('pob');
    }

    // Tanggal Lahir (format standar YYYY-MM-DD)
    protected function dob(): Attribute
    {
        return $this->encryptedAttribute('dob', withHash: true);
    }

    // Agama (hash lowercase agar pencarian "islam" atau "Islam" sama)
    protected function religion(): Attribute
    {
        return $this->encryptedAttribute('religion', withHash: true, lowerHash: true);
    }

    // ========================================================================
    // FINANSIAL (hanya enkripsi, tidak ada hash di migration)
    // ========================================================================

    protected function npwp(): Attribute
    {
        return $this->encryptedAttribute('npwp');
    }

    protected function bankAccount(): Attribute
    {
        return $this->encryptedAttribute('bank_account');
    }

    protected function baseSalary(): Attribute
    {
        return $this->encryptedAttribute('base_salary');
    }

    // ========================================================================
    // KONTAK
    // ========================================================================

    protected function phoneNumber(): Attribute
    {
        return $this->encryptedAttribute('phone_number', withHash: true);
    }

    // Email (hash lowercase karena email tidak membedakan huruf besar/kecil)
    protected function email(): Attribute
    {
        return $this->encryptedAttribute('email', withHash: true, lowerHash: true);
    }

    // ========================================================================
    // ALAMAT
    // ========================================================================

    protected function address(): Attribute
    {
        return $this->encryptedAttribute('address');
    }

    protected function rt(): Attribute
    {
        return $this->encryptedAttribute('rt');
    }

    protected function rw(): Attribute
    {
        return $this->encryptedAttribute('rw');
    }

    protected function village(): Attribute
    {
        return $this->encryptedAttribute('village');
    }

    // Kecamatan (memiliki hash berdasarkan migration)
    protected function district(): Attribute
    {
        return $this->encryptedAttribute('district', withHash: true, lowerHash: true);
    }

    protected function regency(): Attribute
    {
        return $this->encryptedAttribute('regency');
    }

    protected function province(): Attribute
    {
        return $this->encryptedAttribute('province');
    }

    // ========================================================================
    // RELASI
    // ========================================================================

    public function staff()
    {
        return $this->belongsTo(Data::class, 'staff_id');
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    /**
     * Cari staff berdasarkan NIK tanpa perlu dekripsi tiap baris,
     * memanfaatkan kolom nik_hash yang sudah di-index. Dipakai untuk
     * mendeteksi anggota keluarga yang ternyata juga staff.
     */
    public function scopeWhereNik($query, string $nik)
    {
        return $query->where('nik_hash', hash('sha256', trim($nik)));
    }
}
