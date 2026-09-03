<?php

namespace App\Enums\Staff;

enum LivingStatus: string
{
    case ALIVE = 'alive';
    case DECEASED = 'deceased';

    public function label(): string
    {
        return match ($this) {
            self::ALIVE => 'Masih Hidup',
            self::DECEASED => 'Meninggal Dunia',
        };
    }
}
