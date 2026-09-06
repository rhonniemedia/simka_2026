<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'staff_documents';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = ['id'];

    protected $casts = [
        'document_date' => 'date',
    ];

    /*
    |--------------------------------------------------------------------
    | Relasi
    |--------------------------------------------------------------------
    */

    // Pegawai pemilik dokumen ini
    public function staff()
    {
        return $this->belongsTo(Data::class, 'staff_id');
    }

    // Kategori dokumen (mis. Ijazah, SK Pangkat, dll)
    public function category()
    {
        return $this->belongsTo(DocumentCategory::class, 'staff_document_category_id');
    }

    // Record lain yang ditautkan ke dokumen ini (polymorphic),
    // mis. GradeHistory, EducationHistory, dsb - sesuai nullableUuidMorphs('linked_record')
    public function linkedRecord()
    {
        return $this->morphTo();
    }

    // Audit trail: user yang membuat & terakhir mengubah dokumen
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /*
    |--------------------------------------------------------------------
    | Scopes (opsional, membantu query filter di Controller)
    |--------------------------------------------------------------------
    */

    public function scopeVerificationStatus($query, ?string $status)
    {
        return $status ? $query->where('verification_status', $status) : $query;
    }

    public function scopeCategoryId($query, ?string $categoryId)
    {
        return $categoryId ? $query->where('staff_document_category_id', $categoryId) : $query;
    }
}
