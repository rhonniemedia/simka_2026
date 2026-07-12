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
    // IDENTITAS
    // ========================================================================

    protected function nik(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => isset($attributes['nik_encrypted']) ? Crypt::decryptString($attributes['nik_encrypted']) : null,
            set: fn($value) => [
                // trim() digunakan untuk membuang spasi yang tidak sengaja terinput
                'nik_encrypted' => $value ? Crypt::encryptString(trim($value)) : null,
                'nik_hash'      => $value ? hash('sha256', trim($value)) : null,
            ]
        );
    }

    protected function nip(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => isset($attributes['nip_encrypted']) ? Crypt::decryptString($attributes['nip_encrypted']) : null,
            set: fn($value) => [
                'nip_encrypted' => $value ? Crypt::encryptString(trim($value)) : null,
                'nip_hash'      => $value ? hash('sha256', trim($value)) : null,
            ]
        );
    }

    protected function nuptk(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => isset($attributes['nuptk_encrypted']) ? Crypt::decryptString($attributes['nuptk_encrypted']) : null,
            set: fn($value) => [
                'nuptk_encrypted' => $value ? Crypt::encryptString(trim($value)) : null,
                'nuptk_hash'      => $value ? hash('sha256', trim($value)) : null,
            ]
        );
    }

    protected function pob(): Attribute // Tempat Lahir (Hanya enkripsi, tidak ada hash di migration)
    {
        return Attribute::make(
            get: fn($value, $attributes) => isset($attributes['pob_encrypted']) ? Crypt::decryptString($attributes['pob_encrypted']) : null,
            set: fn($value) => [
                'pob_encrypted' => $value ? Crypt::encryptString(trim($value)) : null,
            ]
        );
    }

    protected function dob(): Attribute // Tanggal Lahir (Format standard YYYY-MM-DD, cukup trim)
    {
        return Attribute::make(
            get: fn($value, $attributes) => isset($attributes['dob_encrypted']) ? Crypt::decryptString($attributes['dob_encrypted']) : null,
            set: fn($value) => [
                'dob_encrypted' => $value ? Crypt::encryptString(trim($value)) : null,
                'dob_hash'      => $value ? hash('sha256', trim($value)) : null,
            ]
        );
    }

    protected function religion(): Attribute // Agama (Input teks bebas/pilihan yang rentan huruf besar/kecil)
    {
        return Attribute::make(
            get: fn($value, $attributes) => isset($attributes['religion_encrypted']) ? Crypt::decryptString($attributes['religion_encrypted']) : null,
            set: fn($value) => [
                // Enkripsi tetap menyimpan string asli (misal: "Islam") agar saat didekripsi tampilannya bagus
                'religion_encrypted' => $value ? Crypt::encryptString(trim($value)) : null,
                // Hash diubah ke lowercase agar pencarian "islam" atau "Islam" menghasilkan hash yang sama
                'religion_hash'      => $value ? hash('sha256', strtolower(trim($value))) : null,
            ]
        );
    }

    // ========================================================================
    // FINANSIAL (Hanya enkripsi, tidak ada hash di migration)
    // ========================================================================

    protected function npwp(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => isset($attributes['npwp_encrypted']) ? Crypt::decryptString($attributes['npwp_encrypted']) : null,
            set: fn($value) => [
                'npwp_encrypted' => $value ? Crypt::encryptString(trim($value)) : null,
            ]
        );
    }

    protected function bankAccount(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => isset($attributes['bank_account_encrypted']) ? Crypt::decryptString($attributes['bank_account_encrypted']) : null,
            set: fn($value) => [
                'bank_account_encrypted' => $value ? Crypt::encryptString(trim($value)) : null,
            ]
        );
    }

    protected function baseSalary(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => isset($attributes['base_salary_encrypted']) ? Crypt::decryptString($attributes['base_salary_encrypted']) : null,
            set: fn($value) => [
                'base_salary_encrypted' => $value ? Crypt::encryptString(trim($value)) : null,
            ]
        );
    }

    // ========================================================================
    // KONTAK
    // ========================================================================

    protected function phoneNumber(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => isset($attributes['phone_number_encrypted']) ? Crypt::decryptString($attributes['phone_number_encrypted']) : null,
            set: fn($value) => [
                'phone_number_encrypted' => $value ? Crypt::encryptString(trim($value)) : null,
                'phone_number_hash'      => $value ? hash('sha256', trim($value)) : null,
            ]
        );
    }

    protected function email(): Attribute // Email (Sangat krusial menggunakan strtolower + trim)
    {
        return Attribute::make(
            get: fn($value, $attributes) => isset($attributes['email_encrypted']) ? Crypt::decryptString($attributes['email_encrypted']) : null,
            set: fn($value) => [
                'email_encrypted' => $value ? Crypt::encryptString(trim($value)) : null,
                'email_hash'      => $value ? hash('sha256', strtolower(trim($value))) : null,
            ]
        );
    }

    // ========================================================================
    // ALAMAT
    // ========================================================================

    protected function address(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => isset($attributes['address_encrypted']) ? Crypt::decryptString($attributes['address_encrypted']) : null,
            set: fn($value) => [
                'address_encrypted' => $value ? Crypt::encryptString(trim($value)) : null,
            ]
        );
    }

    protected function rt(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => isset($attributes['rt_encrypted']) ? Crypt::decryptString($attributes['rt_encrypted']) : null,
            set: fn($value) => [
                'rt_encrypted' => $value ? Crypt::encryptString(trim($value)) : null,
            ]
        );
    }

    protected function rw(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => isset($attributes['rw_encrypted']) ? Crypt::decryptString($attributes['rw_encrypted']) : null,
            set: fn($value) => [
                'rw_encrypted' => $value ? Crypt::encryptString(trim($value)) : null,
            ]
        );
    }

    protected function village(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => isset($attributes['village_encrypted']) ? Crypt::decryptString($attributes['village_encrypted']) : null,
            set: fn($value) => [
                'village_encrypted' => $value ? Crypt::encryptString(trim($value)) : null,
            ]
        );
    }

    protected function district(): Attribute // Kecamatan (Memiliki hash berdasarkan migration)
    {
        return Attribute::make(
            get: fn($value, $attributes) => isset($attributes['district_encrypted']) ? Crypt::decryptString($attributes['district_encrypted']) : null,
            set: fn($value) => [
                'district_encrypted' => $value ? Crypt::encryptString(trim($value)) : null,
                'district_hash'      => $value ? hash('sha256', strtolower(trim($value))) : null,
            ]
        );
    }

    protected function regency(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => isset($attributes['regency_encrypted']) ? Crypt::decryptString($attributes['regency_encrypted']) : null,
            set: fn($value) => [
                'regency_encrypted' => $value ? Crypt::encryptString(trim($value)) : null,
            ]
        );
    }

    protected function province(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => isset($attributes['province_encrypted']) ? Crypt::decryptString($attributes['province_encrypted']) : null,
            set: fn($value) => [
                'province_encrypted' => $value ? Crypt::encryptString(trim($value)) : null,
            ]
        );
    }

    // ========================================================================
    // RELASI
    // ========================================================================

    public function staff()
    {
        return $this->belongsTo(Data::class, 'staff_id');
    }
}
