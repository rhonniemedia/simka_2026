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

    /**
     * Accessor untuk mendapatkan nama lengkap beserta gelar.
     * Contoh output: "Dr. Ir. Roni Saputra, S.Kom., M.T."
     */
    public function getNameWithTitleAttribute()
    {
        $front = $this->front_title ? $this->front_title . ' ' : '';
        $back = $this->back_title ? ', ' . $this->back_title : '';

        return $front . $this->name . $back;
    }

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

    /**
     * Relasi ke Jabatan. Sebelumnya method ini belum ada padahal sudah
     * dipakai di DataController::detailEmployment() (->with(['position']))
     * - tanpa ini, pemanggilan relasi itu akan error.
     */
    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    /**
     * Relasi ke Konsentrasi/Jurusan utama staff (bukan authorizedConcentrations
     * yang many-to-many - ini foreign key langsung concentration_id di
     * staff_data).
     */
    public function concentration()
    {
        return $this->belongsTo(CoreConcentration::class, 'concentration_id');
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

    // Tambahkan Relasi Baru Khusus 1 Pendidikan Tertinggi / Terakhir
    public function highestEducation()
    {
        // Mengambil 1 riwayat pendidikan berdasarkan tanggal lulus (graduation_date) paling baru
        return $this->hasOne(EducationHistory::class, 'staff_id')->latestOfMany('graduation_date');
    }

    /**
     * @deprecated Membaca tabel arsip (staff_families_legacy). Untuk data
     * keluarga aktif, pakai familyMembers()/familyRelations().
     */
    public function families()
    {
        return $this->hasMany(Family::class, 'staff_id');
    }

    /**
     * Baris relasi keluarga milik staff ini (data relatif per-staff:
     * relationship, kode HDK, status tunjangan, tanggal nikah).
     */
    public function familyRelations()
    {
        return $this->hasMany(FamilyRelation::class, 'staff_id');
    }

    /**
     * Orang-orang (FamilyMember) yang tertaut ke staff ini. Orang yang
     * sama bisa muncul di banyak staff (mis. anak dari suami-istri yang
     * sama-sama pegawai) - itu ditautkan lewat staff_family_relations,
     * bukan digandakan datanya.
     */
    public function familyMembers()
    {
        return $this->belongsToMany(
            FamilyMember::class,
            'staff_family_relations',
            'staff_id',
            'family_member_id'
        )
            ->withPivot([
                'id',
                'relationship',
                'family_relation_code',
                'payroll_status',
                'marriage_date_encrypted',
                'verification_status',
            ])
            ->withTimestamps();
    }

    // Relasi ke Riwayat Kenaikan Gaji Berkala / KGB (1-to-many)
    public function periodicSalaries()
    {
        return $this->hasMany(PeriodicSalaryHistory::class, 'staff_id');
    }
}
