<?php

namespace App\Enums\Staff;

enum FamilyRelation: string
{
    case SUAMI = 'husband';
    case ISTRI = 'wife';
    case ANAK = 'child';
    case LAINNYA = 'other';

    public function label(): string
    {
        return match ($this) {
            self::SUAMI => 'Suami',
            self::ISTRI => 'Istri',
            self::ANAK => 'Anak',
            self::LAINNYA => 'Lainnya',
        };
    }

    /**
     * Kode HDK Dapodik, diturunkan otomatis dari status hubungan -
     * tidak perlu diinput manual lagi (mencegah tidak sinkron antara
     * Status Hubungan dan kode HDK).
     *
     * CATATAN: kode 1/2/3 dikonfirmasi dari migration
     * update_relationship_enum_in_staff_families_table (HDK 1=Suami,
     * 2=Istri, 3=Anak). Kode '4' untuk Lainnya adalah ASUMSI saya -
     * mohon dikonfirmasi/disesuaikan dengan kode HDK resmi Dapodik.
     */
    public function dapodikCode(): string
    {
        return match ($this) {
            self::SUAMI => '1',
            self::ISTRI => '2',
            self::ANAK => '3',
            self::LAINNYA => '4',
        };
    }
}
