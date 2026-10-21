<?php
// app/Enums/DocumentGroup.php

namespace App\Enums\Staff;

enum DocumentGroup: string
{
    // Nilai lama (sudah ada di database)
    case Akta           = 'akta';
    case Ijazah         = 'ijazah';
    case KartuIdentitas = 'kartu_identitas';
    case Pak            = 'pak';
    case Sertifikat     = 'sertifikat';
    case SkBerkala      = 'sk_berkala';
    case SkFungsional   = 'sk_fungsional';
    case SkPangkat      = 'sk_pangkat';
    case SkPengangkatan = 'sk_pengangkatan';
    case Skp            = 'skp';
    case Spmt           = 'spmt';
    case Lainnya        = 'lainnya';

        // Nilai baru
    case SkMutasi       = 'sk_mutasi';
    case SkPensiun      = 'sk_pensiun';

    public function label(): string
    {
        return match ($this) {
            self::Akta           => 'Akta',
            self::Ijazah         => 'Ijazah & Transkrip',
            self::KartuIdentitas => 'Kartu & Identitas',
            self::Pak            => 'PAK',
            self::Sertifikat     => 'Sertifikat',
            self::SkBerkala      => 'SK Berkala',
            self::SkFungsional   => 'SK Jabatan/Fungsional',
            self::SkPangkat      => 'SK Pangkat',
            self::SkPengangkatan => 'SK Pengangkatan',
            self::Skp            => 'SKP',
            self::Spmt           => 'SPMT',
            self::SkMutasi       => 'SK Mutasi',
            self::SkPensiun      => 'SK Pensiun',
            self::Lainnya        => 'Lainnya',
        };
    }

    // Untuk dropdown: [value => label]
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($c) => [$c->value => $c->label()])
            ->all();
    }
}
