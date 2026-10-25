<?php

namespace App\Models;

use App\Models\Concerns\HasStaffDocuments;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffStatusHistory extends Model
{
    use HasUuids, HasStaffDocuments;

    protected $table = 'staff_status_histories';

    protected $fillable = [
        'staff_id',
        'from_status',
        'to_status',
        'decree_number',
        'decree_date',
        'previous_effective_date',
        'effective_date',
        'destination',
        'note',
        'created_by',
        'updated_by',
        'verification_status',
    ];

    protected $casts = [
        'decree_date'    => 'date',
        'effective_date' => 'date',
        'previous_effective_date' => 'date'
    ];

    protected static function booted(): void
    {
        static::creating(function (self $history) {
            // Status dan TMT sebelum peristiwa, diambil dari data pegawai saat ini
            if (blank($history->from_status)) {
                $history->from_status = $history->staff?->status ?? 'active';
            }
            if (blank($history->previous_effective_date)) {
                $history->previous_effective_date = $history->staff?->status_effective_date;
            }
        });

        $sync = fn(self $h) => $h->staff?->syncStatusFromHistories(
            $h->from_status,
            $h->previous_effective_date
        );

        static::saved($sync);
        static::deleted($sync);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Data::class, 'staff_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function documentLabel(): string
    {
        $status = \App\Http\Controllers\Admin\Career\TransferController::STATUS_LABELS[$this->to_status] ?? $this->to_status;

        return sprintf('%s - TMT %s', $status, $this->formatDocDate($this->effective_date));
    }
}
