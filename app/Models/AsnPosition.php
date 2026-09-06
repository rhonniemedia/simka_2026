<?php

namespace App\Models;

use App\Models\AsnPositionHistory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AsnPosition extends Model
{
    use HasUuids;

    protected $table = 'staff_asn_positions';

    // Disesuaikan dengan kolom yang benar-benar ada di migration
    // (create_staff_asn_positions_table): position_type, eligibility, name.
    // Sebelumnya berisi 'category' yang bukan kolom asli tabel ini, sehingga
    // AsnPosition::create() akan diam-diam membuang position_type &
    // eligibility (mass assignment guard) dan insert akan gagal.
    protected $fillable = [
        'position_type',
        'eligibility',
        'name',
    ];

    public function histories(): HasMany
    {
        return $this->hasMany(AsnPositionHistory::class, 'staff_asn_position_id');
    }
}
