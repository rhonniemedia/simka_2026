<?php

namespace App\Enums\Staff;

enum DocumentGroup: string
{
    case Identity     = 'identity';
    case Appointment  = 'appointment';
    case Status       = 'status';
    case Position     = 'position';
    case Rank         = 'rank';
    case Salary       = 'salary';
    case Education    = 'education';
    case Family       = 'family';
    case Competency   = 'competency';
    case Performance  = 'performance';
    case Retirement   = 'retirement';

    public function label(): string
    {
        return match ($this) {
            self::Identity    => 'Identitas & Kartu',
            self::Appointment => 'Pengangkatan',
            self::Status      => 'Status Kepegawaian',
            self::Position    => 'Jabatan',
            self::Rank        => 'Pangkat / Golongan',
            self::Salary      => 'Gaji & Keuangan',
            self::Education   => 'Pendidikan',
            self::Family      => 'Keluarga',
            self::Competency  => 'Kompetensi & Sertifikasi',
            self::Performance => 'Kinerja & Angka Kredit',
            self::Retirement  => 'Pensiun',
        };
    }

    /** Urutan tampil grup (1 dst.), mengikuti urutan case. */
    public function order(): int
    {
        return array_search($this, self::cases(), true) + 1;
    }

    /** Untuk dropdown/filter: ['identity' => 'Identitas & Kartu', ...] */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn(self $g) => [$g->value => $g->label()])
            ->all();
    }
}
