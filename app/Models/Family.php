<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @deprecated Sejak migrasi ke staff_family_members + staff_family_relations,
 * model ini hanya membaca tabel ARSIP (staff_families_legacy) untuk keperluan
 * audit/riwayat. Untuk data keluarga yang aktif, pakai FamilyMember dan
 * FamilyRelation.
 */
class Family extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'staff_families_legacy';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = ['id'];

    /**
     * Field yang otomatis di-cast.
     */
    protected $casts = [
        'relationship'         => 'string',   // spouse, child, other
        'gender'               => 'string',   // L, P
        'is_studying'          => 'boolean',  // status_sekolah: masih sekolah/kuliah atau tidak
        'payroll_status'       => 'string',   // included, excluded
        'verification_status'  => 'string',   // draft, verified, rejected

        // Laravel encrypted cast: otomatis enkripsi saat set, dekripsi saat get
        'nik_encrypted'            => 'encrypted',
        'birth_place_encrypted'   => 'encrypted',
        'birth_date_encrypted'    => 'encrypted',
        'marriage_date_encrypted' => 'encrypted',
        'telephone_encrypted'     => 'encrypted', // Tambahan untuk telepon
    ];

    /**
     * Field yang tidak boleh ikut ter-serialize ke array/JSON
     * (data sensitif hasil dekripsi).
     */
    protected $hidden = [
        'nik_encrypted',
        'nik_hash',
        'birth_place_encrypted',
        'birth_date_encrypted',
        'marriage_date_encrypted',
        'birth_date_hash',
        'telephone_encrypted', // Tambahan untuk telepon
        'telephone_hash',      // Tambahan untuk telepon
    ];

    /*
    |--------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------
    */

    /**
     * Staff pemilik data keluarga ini.
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Data::class, 'staff_id');
    }

    /**
     * Jenjang pendidikan anggota keluarga.
     */
    public function educationLevel(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class, 'education_level_id');
    }

    /**
     * User yang membuat record.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User yang terakhir mengubah record.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /*
    |--------------------------------------------------------------------
    | Accessors & Mutators (virtual "nik")
    |--------------------------------------------------------------------
    | Sama seperti birth_date: nik_encrypted menyimpan nilai terenkripsi,
    | nik_hash menyimpan hash SHA-256 untuk pencarian/cek duplikat tanpa
    | perlu mendekripsi seluruh baris.
    */

    /**
     * Set NIK: otomatis mengisi nik_encrypted sekaligus nik_hash.
     */
    public function setNikAttribute(?string $value): void
    {
        $this->attributes['nik_encrypted'] = $value
            ? encrypt($value)
            : null;

        $this->attributes['nik_hash'] = $value
            ? hash('sha256', $value)
            : null;
    }

    /**
     * Get NIK dari nilai terdekripsi.
     */
    public function getNikAttribute(): ?string
    {
        return $this->nik_encrypted;
    }

    /*
    |--------------------------------------------------------------------
    | Accessors & Mutators (virtual "birth_date")
    |--------------------------------------------------------------------
    | birth_date_encrypted menyimpan nilai terenkripsi (via cast 'encrypted'),
    | sedangkan birth_date_hash menyimpan hash SHA-256 (bukan bcrypt, karena
    | perlu deterministik) agar bisa dicari dengan whereBirthDateHash tanpa
    | membuka enkripsi seluruh baris.
    */

    /**
     * Set tanggal lahir: otomatis mengisi birth_date_encrypted
     * sekaligus birth_date_hash untuk keperluan pencarian.
     */
    public function setBirthDateAttribute(?string $value): void
    {
        $this->attributes['birth_date_encrypted'] = $value
            ? encrypt($value)
            : null;

        $this->attributes['birth_date_hash'] = $value
            ? hash('sha256', $value)
            : null;
    }

    /**
     * Get tanggal lahir dari nilai terdekripsi.
     */
    public function getBirthDateAttribute(): ?string
    {
        return $this->birth_date_encrypted;
    }

    /*
    |--------------------------------------------------------------------
    | Accessors & Mutators (virtual "telephone")
    |--------------------------------------------------------------------
    */

    /**
     * Set Telepon: otomatis mengisi telephone_encrypted sekaligus telephone_hash.
     */
    public function setTelephoneAttribute(?string $value): void
    {
        $this->attributes['telephone_encrypted'] = $value
            ? encrypt($value)
            : null;

        $this->attributes['telephone_hash'] = $value
            ? hash('sha256', $value)
            : null;
    }

    /**
     * Get Telepon dari nilai terdekripsi.
     */
    public function getTelephoneAttribute(): ?string
    {
        return $this->telephone_encrypted;
    }

    /*
    |--------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------
    */

    /**
     * Cari berdasarkan NIK tanpa perlu dekripsi tiap baris,
     * memanfaatkan kolom nik_hash yang sudah di-index.
     */
    public function scopeWhereNik($query, string $nik)
    {
        return $query->where('nik_hash', hash('sha256', $nik));
    }

    /**
     * Cari berdasarkan tanggal lahir tanpa perlu dekripsi tiap baris,
     * memanfaatkan kolom birth_date_hash yang sudah di-index.
     */
    public function scopeWhereBirthDate($query, string $date)
    {
        return $query->where('birth_date_hash', hash('sha256', $date));
    }

    /**
     * Cari berdasarkan nomor telepon menggunakan hash (tanpa dekripsi).
     */
    public function scopeWhereTelephone($query, string $telephone)
    {
        return $query->where('telephone_hash', hash('sha256', $telephone));
    }

    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    public function scopeIncludedInPayroll($query)
    {
        return $query->where('payroll_status', 'included');
    }
}
