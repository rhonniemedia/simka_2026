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
}
