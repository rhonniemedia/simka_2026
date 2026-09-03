<?php

namespace App\Enums\Staff;

enum MutationStatus: string
{
    case TRANSFER_IN = 'transfer_in';
    case TRANSFER_OUT = 'transfer_out';

    public function label(): string
    {
        return match ($this) {
            self::TRANSFER_IN => 'Pindah Masuk',
            self::TRANSFER_OUT => 'Pindah Keluar',
        };
    }
}
