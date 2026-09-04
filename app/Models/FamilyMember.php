<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

/**
 * Satu baris = satu ORANG fisik (bukan satu relasi ke staff).
 * Bisa ditautkan ke lebih dari satu staff lewat FamilyRelation -
 * ini yang menyelesaikan kasus anak/pasangan yang sama-sama tercatat
 * di dua staff berbeda (mis. suami-istri yang sama-sama pegawai).
 */
class FamilyMember extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'staff_family_members';

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = ['id'];

    protected $casts = [
        'gender'               => 'string',
        'is_studying'          => 'boolean',
        'verification_status'  => 'string',
    ];

    protected $hidden = [
        'nik_encrypted',
        'nik_hash',
        'birth_place_encrypted',
        'birth_date_encrypted',
        'birth_date_hash',
        'telephone_encrypted',
        'telephone_hash',
    ];

    /*
    |--------------------------------------------------------------------
    | Accessors & Mutators (virtual "nik", "birthPlace", "birthDate", "telephone")
    |--------------------------------------------------------------------
    | Pola disamakan dengan DataVault: Crypt::encryptString/decryptString
    | (bukan encrypt()/decrypt() global) supaya konsisten satu skema
    | enkripsi di seluruh aplikasi. Hash selalu di-trim dulu sebelum
    | di-hash, agar bisa dicocokkan dengan nik_hash di staff_data_vault.
    */

    protected function nik(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => isset($attributes['nik_encrypted'])
                ? Crypt::decryptString($attributes['nik_encrypted'])
                : null,
            set: fn($value) => [
                'nik_encrypted' => $value ? Crypt::encryptString(trim($value)) : null,
                'nik_hash'      => $value ? hash('sha256', trim($value)) : null,
            ]
        );
    }

    protected function birthPlace(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => isset($attributes['birth_place_encrypted'])
                ? Crypt::decryptString($attributes['birth_place_encrypted'])
                : null,
            set: fn($value) => [
                'birth_place_encrypted' => $value ? Crypt::encryptString(trim($value)) : null,
            ]
        );
    }

    protected function birthDate(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => isset($attributes['birth_date_encrypted'])
                ? Crypt::decryptString($attributes['birth_date_encrypted'])
                : null,
            set: fn($value) => [
                'birth_date_encrypted' => $value ? Crypt::encryptString(trim($value)) : null,
                'birth_date_hash'      => $value ? hash('sha256', trim($value)) : null,
            ]
        );
    }

    protected function telephone(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => isset($attributes['telephone_encrypted'])
                ? Crypt::decryptString($attributes['telephone_encrypted'])
                : null,
            set: fn($value) => [
                'telephone_encrypted' => $value ? Crypt::encryptString(trim($value)) : null,
                'telephone_hash'      => $value ? hash('sha256', trim($value)) : null,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------
    */

    public function educationLevel(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class, 'education_level_id');
    }

    /**
     * Terisi kalau orang ini ternyata juga tercatat sebagai staff sendiri
     * (NIK cocok dengan staff_data_vault). Berguna untuk kasus
     * pasangan suami-istri yang sama-sama pegawai.
     */
    public function linkedStaff(): BelongsTo
    {
        return $this->belongsTo(Data::class, 'linked_staff_id');
    }

    public function relations(): HasMany
    {
        return $this->hasMany(FamilyRelation::class, 'family_member_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /*
    |--------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------
    */

    /**
     * Cari orang yang sudah ada berdasarkan NIK, tanpa perlu dekripsi
     * tiap baris - dipakai controller sebelum membuat baris baru,
     * supaya NIK yang sama bisa "ditautkan" bukan dianggap error.
     */
    public function scopeWhereNik($query, string $nik)
    {
        return $query->where('nik_hash', hash('sha256', trim($nik)));
    }
}
