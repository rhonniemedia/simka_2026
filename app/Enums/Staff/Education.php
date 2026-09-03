<?php

namespace App\Enums\Staff;

enum Education: string
{
    case TIDAK_SEKOLAH = 'tidak-sekolah';
    case SD = 'sd-sederajat';
    case SMP = 'smp-sederajat';
    case SMA = 'sma-sederajat';
    case DIPLOMA_1 = 'diploma-1';
    case DIPLOMA_2 = 'diploma-2';
    case DIPLOMA_3 = 'diploma-3';
    case SARJANA = 'sarjana';
    case MAGISTER = 'magister';
    case DOKTOR = 'doktor';

    public function label(): string
    {
        return match ($this) {
            self::TIDAK_SEKOLAH => 'Tidak Sekolah',
            self::SD => 'SD / Sederajat',
            self::SMP => 'SMP / Sederajat',
            self::SMA => 'SMA / Sederajat',
            self::DIPLOMA_1 => 'Diploma 1',
            self::DIPLOMA_2 => 'Diploma 2',
            self::DIPLOMA_3 => 'Diploma 3',
            self::SARJANA => 'Diploma 4/Sarjana (D4/S1)',
            self::MAGISTER => 'Magister (S2)',
            self::DOKTOR => 'Doktor (S3)',
        };
    }
}
