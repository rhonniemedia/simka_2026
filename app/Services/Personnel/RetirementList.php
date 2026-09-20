<?php

namespace App\Services\Personnel;

use DateTimeInterface;

/**
 * Menyusun, memfilter, dan mengurutkan daftar pensiun di memori.
 *
 * Dilakukan di PHP karena tanggal lahir tersimpan terenkripsi, sehingga umur
 * tidak bisa dihitung, difilter, atau diurutkan lewat SQL.
 *
 * Bentuk satu baris:
 *   [
 *     'staff'    => model Data,
 *     'position' => model AsnPosition aktif atau null,
 *     'calc'     => hasil RetirementCalculator::calculate(),
 *     'tmt'      => TMT yang ditampilkan (untuk yang sudah pensiun: tanggal
 *                   tercatat di status_effective_date, selain itu hasil hitung),
 *   ]
 */
final class RetirementList
{
    public const RETIRED_STATUS = 'retired';

    public static function makeRow(object $staff, ?object $position, mixed $dob, ?DateTimeInterface $today = null): array
    {
        $retired = ($staff->status ?? null) === self::RETIRED_STATUS;

        $limitAge = ($position !== null && ($position->retirement_age ?? null) !== null)
            ? (int) $position->retirement_age
            : null;

        $calc = RetirementCalculator::calculate($dob, $limitAge, $today, $retired);

        $tmt = $calc['tmt'];
        if ($retired) {
            $tmt = RetirementCalculator::parseDate($staff->status_effective_date ?? null) ?? $tmt;
        }

        return [
            'staff' => $staff,
            'position' => $position,
            'calc' => $calc,
            'tmt' => $tmt,
        ];
    }

    /**
     * @param array<int, array> $rows
     * @param array{search?:string, retirement_status?:string, employment_status?:string,
     *              position_type?:string, year?:string, gender?:string} $filters
     * @return array<int, array>
     */
    public static function filter(array $rows, array $filters): array
    {
        $search = mb_strtolower(trim((string) ($filters['search'] ?? '')));
        $status = self::clean($filters['retirement_status'] ?? null);
        $employment = self::clean($filters['employment_status'] ?? null);
        $positionType = self::clean($filters['position_type'] ?? null);
        $year = self::clean($filters['year'] ?? null);
        $gender = self::clean($filters['gender'] ?? null);

        return array_values(array_filter($rows, function (array $row) use ($search, $status, $employment, $positionType, $year, $gender) {
            $staff = $row['staff'];

            if ($search !== '' && mb_strpos(mb_strtolower((string) $staff->name), $search) === false) {
                return false;
            }
            if ($status !== null && $row['calc']['state'] !== $status) {
                return false;
            }
            if ($employment !== null && (string) $staff->employment_id !== $employment) {
                return false;
            }
            if ($positionType !== null && ($row['position']->position_type ?? null) !== $positionType) {
                return false;
            }
            if ($year !== null && ($row['tmt'] === null || $row['tmt']->format('Y') !== $year)) {
                return false;
            }
            if ($gender !== null && (string) $staff->gender !== $gender) {
                return false;
            }

            return true;
        }));
    }

    /**
     * Urutan: (0) yang belum pensiun, dari batas usia terdekat (yang sudah
     * lewat berada paling atas); (1) tanggal lahir tidak valid; (2) yang sudah
     * pensiun, dari TMT terbaru. Nama menjadi pembeda.
     *
     * @param array<int, array> $rows
     * @return array<int, array>
     */
    public static function sort(array $rows): array
    {
        usort($rows, function (array $a, array $b) {
            $groupA = self::group($a);
            $groupB = self::group($b);

            if ($groupA !== $groupB) {
                return $groupA <=> $groupB;
            }

            if ($groupA === 0) {
                $cmp = $a['calc']['limit_date'] <=> $b['calc']['limit_date'];
                if ($cmp !== 0) {
                    return $cmp;
                }
            } elseif ($groupA === 2) {
                $cmp = ($b['tmt']?->getTimestamp() ?? 0) <=> ($a['tmt']?->getTimestamp() ?? 0);
                if ($cmp !== 0) {
                    return $cmp;
                }
            }

            return strcmp(mb_strtolower((string) $a['staff']->name), mb_strtolower((string) $b['staff']->name));
        });

        return $rows;
    }

    /**
     * Pilihan tahun pensiun (dari TMT) untuk filter, dari yang terlama.
     *
     * @param array<int, array> $rows
     * @return array<int, array{value:string,label:string}>
     */
    public static function yearOptions(array $rows): array
    {
        $years = [];
        foreach ($rows as $row) {
            if ($row['tmt'] !== null) {
                $years[$row['tmt']->format('Y')] = true;
            }
        }

        $years = array_keys($years);
        sort($years);

        return array_map(fn($y) => ['value' => (string) $y, 'label' => (string) $y], $years);
    }

    private static function group(array $row): int
    {
        if ($row['calc']['state'] === RetirementCalculator::STATE_RETIRED) {
            return 2;
        }

        return $row['calc']['limit_date'] === null ? 1 : 0;
    }

    private static function clean(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : $value;
    }
}
