<?php

namespace App\Models;

use App\Enums\Staff\DocumentGroup;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentCategory extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'staff_document_categories';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = ['id'];

    /**
     * Tabel yang boleh dijadikan target tautan dokumen.
     * Kunci = nama tabel (dipakai di linked_table dan linked_record_type),
     * nilai = label di dropdown.
     */
    public const LINKABLE_TABLES = [
        'staff_education_histories'       => 'Riwayat Pendidikan',
        'staff_family_relations'          => 'Keluarga (Pasangan/Anak)',
        'staff_asn_positions_histories'   => 'Riwayat Jabatan',
        'staff_periodic_salary_histories' => 'Riwayat Gaji Berkala',
        'staff_grade_histories'           => 'Riwayat Kepangkatan',
        'staff_status_histories'          => 'Riwayat Mutasi/Pensiun',
    ];

    protected function casts(): array
    {
        return [
            'group'     => DocumentGroup::class,
            'is_active' => 'boolean',
            'requires_document_number' => 'boolean',
        ];
    }
}
