<?php

namespace App\Enums\Staff;

enum StaffStatus: string
{
    case AKTIF = 'active';
    case TIDAK_AKTIF = 'inactive';
    case PENSIUN = 'retired';
    case MENGUNDURKAN_DIRI = 'resigned';

    public function label(): string
    {
        return match ($this) {
            self::AKTIF => 'Aktif',
            self::TIDAK_AKTIF => 'Tidak Aktif',
            self::PENSIUN => 'Pensiun',
            self::MENGUNDURKAN_DIRI => 'Mengundurkan Diri',
        };
    }
}
