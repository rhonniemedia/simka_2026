<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsnPositionHistory extends Model
{
    use HasUuids;

    protected $table = 'staff_asn_positions_histories';

    protected $fillable = [
        'staff_id',
        'staff_asn_position_id',
        'decree_number',
        'decree_date',
        'effective_date',
        'is_active',
    ];

    protected $casts = [
        'decree_date' => 'date',
        'effective_date' => 'date',
        'is_active' => 'boolean',
    ];

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
}
