<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationHistory extends Model
{
    use HasFactory, HasUuids;

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
}
