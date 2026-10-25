<?php

namespace App\Models;

use App\Models\Concerns\HasStaffDocuments;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsnPositionHistory extends Model
{
    use HasUuids, HasStaffDocuments;

    protected $table = 'staff_asn_positions_histories';

    protected $fillable = [
        'staff_id',
        'staff_asn_position_id',
        'decree_number',
        'decree_date',
        'effective_date',
        'is_active',
        'created_by',
        'updated_by',
        'verification_status',
    ];

    protected $casts = [
        'decree_date' => 'date',
        'effective_date' => 'date',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $history) {
            if ($history->is_active) {
                static::where('staff_id', $history->staff_id)
                    ->where('id', '!=', $history->id)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }
        });
    }

    /**
     * Relasi ke data pegawai utama
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Data::class, 'staff_id');
    }

    /**
     * Relasi ke data master jabatan ASN
     */
    public function asnPosition(): BelongsTo
    {
        return $this->belongsTo(AsnPosition::class, 'staff_asn_position_id');
    }

    public function documentLabel(): string
    {
        return sprintf('Jabatan %s - TMT %s', $this->asnPosition?->name ?? '-', $this->formatDocDate($this->effective_date));
    }
}
