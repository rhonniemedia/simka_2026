<?php

namespace App\Models;

use App\Enums\Staff\FamilyRelation as FamilyRelationEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Relasi antara satu staff dan satu FamilyMember (orang). Field yang
 * maknanya relatif terhadap staff (relationship, kode HDK, status
 * tunjangan, tanggal nikah) tinggal di sini - bukan di FamilyMember -
 * karena bisa berbeda untuk tiap staff yang menautkan orang yang sama.
 *
 * CATATAN NAMA: class ini sengaja bernama sama dengan enum
 * App\Enums\Staff\FamilyRelation (beda namespace, jadi PHP tidak bentrok).
 * Tapi kalau file lain butuh pakai keduanya sekaligus, salah satunya WAJIB
 * di-alias, misal:
 *   use App\Models\FamilyRelation;
 *   use App\Enums\Staff\FamilyRelation as FamilyRelationEnum;
 */
class FamilyRelation extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'staff_family_relations';

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = ['id'];

    protected $casts = [
        'relationship'            => 'string',
        'payroll_status'          => 'string',
        'verification_status'     => 'string',
        'marriage_date_encrypted' => 'encrypted',
    ];

    protected $hidden = [
        'marriage_date_encrypted',
    ];

    /*
    |--------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------
    */

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Data::class, 'staff_id');
    }

    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'family_member_id');
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
    | Helpers
    |--------------------------------------------------------------------
    */

    /**
     * Instance enum FamilyRelation dari nilai relationship, untuk label
     * & keperluan tampilan (lihat App\Enums\Staff\FamilyRelation).
     */
    public function getRelationshipEnumAttribute(): ?FamilyRelationEnum
    {
        return FamilyRelationEnum::tryFrom($this->relationship);
    }

    /*
    |--------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------
    */

    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    public function scopeIncludedInPayroll($query)
    {
        return $query->where('payroll_status', 'included');
    }
}
