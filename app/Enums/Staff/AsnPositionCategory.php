<?php

namespace App\Enums\Staff;

enum AsnPositionCategory: string
{
    case FUNGSIONAL = 'fungsional';
    case PELAKSANA = 'pelaksana';
    case PENGAWAS = 'pengawas';
    case ADMINISTRATOR = 'administrator';
    case PIMPINAN_TINGGI = 'pimpinan-tinggi';

    public function label(): string
    {
        return match ($this) {
            self::FUNGSIONAL => 'Jabatan Fungsional',
            self::PELAKSANA => 'Jabatan Pelaksana',
            self::PENGAWAS => 'Jabatan Pengawas',
            self::ADMINISTRATOR => 'Jabatan Administrator',
            self::PIMPINAN_TINGGI => 'Jabatan Pimpinan Tinggi',
        };
    }
}
