<?php

namespace App\Enums\Staff;

enum Income: string
{
    case KURANG_1_JT = 'kurang-1jt';
    case RANGE_1_2_JT = '1-2jt';
    case RANGE_2_3_JT = '2-3jt';
    case RANGE_3_5_JT = '3-5jt';
    case RANGE_5_10_JT = '5-10jt';
    case LEBIH_10_JT = 'lebih-10jt';
    case TANPA_PENGHASILAN = 'tanpa-penghasilan';

    public function label(): string
    {
        return match ($this) {
            self::KURANG_1_JT => 'Kurang dari Rp1.000.000',
            self::RANGE_1_2_JT => 'Rp1.000.000 - Rp1.999.999',
            self::RANGE_2_3_JT => 'Rp2.000.000 - Rp2.999.999',
            self::RANGE_3_5_JT => 'Rp3.000.000 - Rp4.999.999',
            self::RANGE_5_10_JT => 'Rp5.000.000 - Rp9.999.999',
            self::LEBIH_10_JT => 'Rp10.000.000 atau lebih',
            self::TANPA_PENGHASILAN => 'Tidak Berpenghasilan',
        };
    }
}
