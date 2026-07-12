<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeHistory extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'staff_grade_histories';

    // Properti ini dihapus karena sudah otomatis ditangani oleh trait HasUuids
    // public $incrementing = false;
    // protected $keyType = 'string';

    protected $guarded = ['id'];

    /**
     * Otomatis mengonversi tipe data saat diambil dari database.
     */
    protected $casts = [
        'decree_date'          => 'date',
        'effective_date'       => 'date',
        'approval_date'        => 'date',
        'service_period_years' => 'integer',
    ];

    /**
     * Relasi ke data utama pegawai
     */
    public function staff(): BelongsTo
    {
        // Sesuaikan 'StaffData::class' dengan nama model pegawai Anda yang sebenarnya
        return $this->belongsTo(Data::class, 'staff_id');
    }

    /**
     * Relasi ke data master golongan
     */
    public function grade(): BelongsTo
    {
        // Sesuaikan 'StaffGrade::class' dengan nama model master golongan Anda
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    /**
     * Relasi Audit Trail (Siapa yang membuat/mengubah)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
