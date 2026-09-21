<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffStatusHistory extends Model
{
    use HasUuids;

    protected $table = 'staff_status_histories';

    protected $fillable = [
        'staff_id',
        'from_status',
        'to_status',
        'decree_number',
        'effective_date',
        'note',
        'created_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Data::class, 'staff_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
