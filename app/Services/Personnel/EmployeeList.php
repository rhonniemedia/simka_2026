<?php

namespace App\Services\Personnel;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Daftar pegawai aktif per kelompok (status kepegawaian x jenis personel),
 * terurut kepangkatan di dalam tiap kelompok.
 */
final class EmployeeList
{
    /**
     * @return array<int, array{employment:string, ptk:string, rows:array<int, array<string,string|int>>}>
     */
    public static function build(?DateTimeInterface $today = null, ?string $employmentId = null): array
    {
        $today = $today
            ? DateTimeImmutable::createFromInterface($today)->setTime(0, 0)
            : new DateTimeImmutable('today');

        $groups = [];

        foreach (SeniorityList::ranked($today, requireGrade: false, employmentId: $employmentId) as $e) {
            $staff = $e['staff'];
            $key   = $staff->employment_id . '|' . $staff->personnel_id;

            $groups[$key] ??= [
                'sort'       => [
                    (string) ($staff->employmentStatus?->code ?? ''),
                    (string) ($staff->personnelType?->code ?? ''),
                ],
                'employment' => $staff->employmentStatus?->alias ?? '-',
                'ptk'        => $staff->personnelType?->name ?? '-',
                'rows'       => [],
            ];

            $tmt = RetirementCalculator::parseDate($staff->status_effective_date);

            $groups[$key]['rows'][] = [
                'no'     => count($groups[$key]['rows']) + 1,
                'name'   => $staff->name_with_title,
                'nip'    => $e['nip'],
                'gender' => $staff->gender,
                'grade'  => $e['grade']?->grade_code ?? '-',
                'note'   => $tmt ? 'TMT ' . SeniorityList::formatDate($tmt) : '-',
            ];
        }

        // Urutan kelompok: kode status kepegawaian, lalu kode jenis personel.
        uasort($groups, fn(array $a, array $b) => strnatcasecmp($a['sort'][0], $b['sort'][0])
            ?: strnatcasecmp($a['sort'][1], $b['sort'][1]));

        return array_values(array_map(fn(array $g) => array_diff_key($g, ['sort' => true]), $groups));
    }
}
