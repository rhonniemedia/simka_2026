<?php

namespace App\Models;

use App\Models\CoreConcentration;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Data extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'staff_data';

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = ['id'];

    // Relasi ke tabel Vault (1-to-1)
    public function vault()
    {
        return $this->hasOne(DataVault::class, 'staff_id');
    }

    // Relasi ke tabel Konsentrasi / Jurusan yang dipegang staf ini
    public function authorizedConcentrations()
    {
        return $this->belongsToMany(
            CoreConcentration::class,
            'staff_concentration_authorizations',
            'staff_id',
            'concentration_id'
        );
    }

    public function personnelType()
    {
        // Sesuaikan 'personel_type_id' dengan nama kolom foreign key yang ada di tabel staff_data
        return $this->belongsTo(PersonnelType::class, 'personnel_id');
    }

    public function employmentStatus()
    {
        // Sesuaikan 'personel_type_id' dengan nama kolom foreign key yang ada di tabel staff_data
        return $this->belongsTo(EmploymentStatus::class, 'employment_id');
    }

    // ========================================================================
    // RELASI KEPEGAWAIAN BARU
    // ========================================================================

    // Relasi ke Dokumen Pegawai (1-to-many)
    public function documents()
    {
        return $this->hasMany(Document::class, 'staff_id');
    }

    // Relasi ke Riwayat Kepangkatan (1-to-many)
    public function grade()
    {
        return $this->hasMany(GradeHistory::class, 'staff_id');
    }

    // Relasi ke Riwayat Pendidikan (1-to-many)
    public function educations()
    {
        return $this->hasMany(EducationHistory::class, 'staff_id');
    }

    // Relasi ke Data Keluarga (1-to-many)
    public function families()
    {
        return $this->hasMany(Family::class, 'staff_id');
    }

    // Relasi ke Riwayat Kenaikan Gaji Berkala / KGB (1-to-many)
    public function periodicSalaries()
    {
        return $this->hasMany(PeriodicSalaryHistory::class, 'staff_id');
    }
}
