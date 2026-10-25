<?php

namespace App\Models;

use App\Models\Concerns\HasStaffDocuments;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationHistory extends Model
{
    use HasFactory, HasUuids, HasStaffDocuments;

    protected $table = 'staff_education_histories';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = ['id'];

    /**
     * Relasi ke referensi tingkat pendidikan (staff_education_levels)
     */
    public function level()
    {
        // Sesuaikan EducationLevel::class dengan nama model referensi pendidikan Anda.
        // Kolom 'education_level_id' merujuk pada struktur tabel staff_education_histories.
        return $this->belongsTo(EducationLevel::class, 'education_level_id');
    }

    public function documentLabel(): string
    {
        return sprintf('Pendidikan %s - Lulus %s', $this->level?->name ?? '-', $this->formatDocDate($this->graduation_date));
    }
}
