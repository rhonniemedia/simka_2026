<?php

namespace App\Models;

use App\Enums\Staff\AsnPositionCategory; // Import Enum yang baru dibuat
use App\Models\AsnPositionHistory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffAsnPosition extends Model
{
    use HasUuids;

    protected $table = 'staff_asn_positions';

    protected $fillable = [
        'category',
        'name',
    ];

    // Beritahu Laravel untuk mengonversi kolom 'category' menjadi objek Enum
    protected $casts = [
        'category' => AsnPositionCategory::class,
    ];

    public function histories(): HasMany
    {
        return $this->hasMany(AsnPositionHistory::class, 'staff_asn_position_id');
    }
}
