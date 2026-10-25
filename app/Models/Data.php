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

    protected $casts = [
        'is_draft' => 'boolean',
    ];

    /**
     * Data yang sudah lengkap (bukan draft form bertahap). Dipakai oleh
     * daftar & statistik agar draft yang belum selesai tidak ikut tampil.
     * Nama kolom diberi prefix tabel supaya aman dipakai bersama join.
     */
    public function scopeCompleted($query)
    {
        return $query->where($this->getTable() . '.is_draft', false);
    }

    /**
     * Data yang baru tersimpan sebagian lewat form bertahap.
     */
    public function scopeDraft($query)
    {
        return $query->where($this->getTable() . '.is_draft', true);
    }

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

    // Relasi ke Riwayat Status: mutasi, pensiun, dst. (1-to-many)
    public function statusHistories()
    {
        return $this->hasMany(StaffStatusHistory::class, 'staff_id');
    }

    /**
     * Hitung ulang status terkini dari riwayat terbaru (selain yang ditolak).
     * Jika tidak ada riwayat yang tersisa, status dikembalikan ke $fallbackStatus
     * (status sebelum peristiwa yang dihapus/ditolak), bukan di-reset ke 'active'.
     */
    public function syncStatusFromHistories(?string $fallbackStatus = null, $fallbackDate = null): void
    {
        $latest = $this->statusHistories()
            ->where('verification_status', '!=', 'rejected')
            ->orderByDesc('effective_date')
            ->orderByDesc('created_at')
            ->first();

        if ($latest) {
            $this->update([
                'status'                => $latest->to_status,
                'status_effective_date' => $latest->effective_date,
            ]);
        } elseif ($fallbackStatus) {
            $this->update([
                'status'                => $fallbackStatus,
                // TMT lama dipulihkan; jika tidak diketahui, biarkan nilai sekarang
                'status_effective_date' => $fallbackDate ?? $this->status_effective_date,
            ]);
        }
    }

    /**
     * Status & TMT pegawai non-aktif dikelola lewat riwayat (menu Mutasi/Pensiun),
     * bukan lewat form edit data pegawai.
     */
    public function isStatusLocked(): bool
    {
        return $this->status !== 'active';
    }
}
