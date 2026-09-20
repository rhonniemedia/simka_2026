<?php

namespace App\Enums\Staff;

enum Religion: string
{
    case ISLAM = 'islam';
    case KRISTEN = 'kristen';
    case KATOLIK = 'katolik';
    case HINDU = 'hindu';
    case BUDDHA = 'buddha';
    case KONGHUCU = 'konghucu';
    case LAINNYA = 'lainnya';

    /**
     * Kode angka lama (urutan standar Dapodik) yang pernah tersimpan di database.
     */
    private const LEGACY_CODES = [
        '1' => self::ISLAM,
        '2' => self::KRISTEN,
        '3' => self::KATOLIK,
        '4' => self::HINDU,
        '5' => self::BUDDHA,
        '6' => self::KONGHUCU,
        '7' => self::LAINNYA,
    ];

    /**
     * Ubah nilai agama yang tersimpan (value enum, kode angka lama, label,
     * atau nama case; huruf besar/kecil diabaikan) menjadi case enum.
     * Mengembalikan null bila tidak dikenali.
     */
    public static function fromStored(mixed $value): ?self
    {
        if ($value instanceof self) {
            return $value;
        }

        $needle = mb_strtolower(trim((string) $value));

        if ($needle === '') {
            return null;
        }

        if (isset(self::LEGACY_CODES[$needle])) {
            return self::LEGACY_CODES[$needle];
        }

        foreach (self::cases() as $case) {
            if (
                $needle === $case->value
                || $needle === mb_strtolower($case->label())
                || $needle === mb_strtolower($case->name)
            ) {
                return $case;
            }
        }

        return null;
    }

    public function label(): string
    {
        return match ($this) {
            self::ISLAM => 'Islam',
            self::KRISTEN => 'Kristen',
            self::KATOLIK => 'Katolik',
            self::HINDU => 'Hindu',
            self::BUDDHA => 'Buddha',
            self::KONGHUCU => 'Konghucu',
            self::LAINNYA => 'Lainnya',
        };
    }
}
