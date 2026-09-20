<?php

namespace App\Models;

use App\Models\AsnPositionHistory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AsnPosition extends Model
{
    use HasUuids;

    /** Batas usia pensiun bawaan (tahun). */
    public const DEFAULT_RETIREMENT_AGE = 58;

    /** Batas usia pensiun Fungsional Keahlian Guru (tahun). */
    public const TEACHER_RETIREMENT_AGE = 60;

    protected $table = 'staff_asn_positions';

    // Disesuaikan dengan kolom yang benar-benar ada di migration
    // (create_staff_asn_positions_table): position_type, eligibility, name,
    // ditambah retirement_age (add_retirement_age_to_staff_asn_positions_table).
    protected $fillable = [
        'position_type',
        'eligibility',
        'name',
        'retirement_age',
    ];

    protected $casts = [
        'retirement_age' => 'integer',
    ];

    /**
     * Jabatan baru yang dibuat tanpa mengisi retirement_age (mis. lewat form
     * master yang belum memuat kolom ini) otomatis mendapat batas usia sesuai
     * aturan, bukan asal 58.
     */
    protected static function booted(): void
    {
        static::creating(function (AsnPosition $position) {
            if (blank($position->retirement_age)) {
                $position->retirement_age = self::defaultRetirementAge(
                    $position->position_type,
                    $position->name
                );
            }
        });
    }

    /**
     * Aturan batas usia pensiun: Fungsional Keahlian dengan nama diawali
     * "Guru" = 60 tahun, selain itu 58 tahun.
     */
    public static function defaultRetirementAge(?string $positionType, ?string $name): int
    {
        $isTeacher = $positionType === 'fungsional_keahlian'
            && preg_match('/^Guru(\s|$)/i', trim((string) $name)) === 1;

        return $isTeacher ? self::TEACHER_RETIREMENT_AGE : self::DEFAULT_RETIREMENT_AGE;
    }

    public function histories(): HasMany
    {
        return $this->hasMany(AsnPositionHistory::class, 'staff_asn_position_id');
    }
}
