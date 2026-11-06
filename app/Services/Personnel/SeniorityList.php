<?php

namespace App\Services\Personnel;

use App\Models\AsnPositionHistory;
use App\Models\Data;
use App\Models\EducationHistory;
use App\Models\GradeHistory;
use Carbon\Carbon;
use DateTimeImmutable;
use DateTimeInterface;
use Throwable;

/**
 * Pengurutan kepangkatan pegawai aktif, dipakai bersama oleh DUK dan Daftar Pegawai.
 *
 * Urutan: golongan tertinggi, TMT pangkat paling lama, masa kerja seluruh
 * terbanyak, tanggal lahir tertua, lalu nama. Pegawai tanpa golongan
 * otomatis berada di bawah (diurutkan tanggal lahir lalu nama).
 */
final class SeniorityList
{
    /**
     * Pegawai aktif (data lengkap) terurut kepangkatan.
     *
     * @param bool        $requireGrade  true = hanya yang punya riwayat pangkat (untuk DUK)
     * @param string|null $employmentId  batasi pada satu status kepegawaian
     * @return array<int, array<string, mixed>>
     */
    public static function ranked(
        DateTimeInterface $today,
        bool $requireGrade = true,
        ?string $employmentId = null
    ): array {
        $today = DateTimeImmutable::createFromInterface($today)->setTime(0, 0);

        $staffList = Data::completed()
            ->where('staff_data.status', 'active')
            ->when($employmentId, fn($q) => $q->where('staff_data.employment_id', $employmentId))
            ->with(['vault', 'employmentStatus', 'personnelType'])
            ->get();

        if ($staffList->isEmpty()) {
            return [];
        }

        $ids = $staffList->pluck('id');

        // Riwayat terbaru per pegawai (urutan menurun, lalu unique mengambil yang pertama).
        $grades = GradeHistory::with('grade')
            ->whereIn('staff_id', $ids)
            ->where('verification_status', '!=', 'rejected')
            ->orderByDesc('effective_date')
            ->orderByDesc('created_at')
            ->get()
            ->unique('staff_id')
            ->keyBy('staff_id');

        $positions = AsnPositionHistory::with('asnPosition')
            ->whereIn('staff_id', $ids)
            ->where('is_active', true)
            ->orderByDesc('effective_date')
            ->get()
            ->unique('staff_id')
            ->keyBy('staff_id');

        $educations = EducationHistory::with('level')
            ->whereIn('staff_id', $ids)
            ->where('verification_status', '!=', 'rejected')
            ->orderByDesc('graduation_date')
            ->orderByDesc('created_at')
            ->get()
            ->unique('staff_id')
            ->keyBy('staff_id');

        $entries = [];

        foreach ($staffList as $staff) {
            $gradeHistory = $grades->get($staff->id);
            if ($requireGrade && ! $gradeHistory) {
                continue;
            }

            $dobRaw    = self::vault($staff, 'dob');
            $gradeTmt  = RetirementCalculator::parseDate($gradeHistory?->effective_date);
            $startDate = RetirementCalculator::parseDate($staff->prior_service_period_effective_date);
            $nip       = self::vault($staff, 'nip');

            $entries[] = [
                'staff'     => $staff,
                'grade'     => $gradeHistory?->grade,
                'position'  => $positions->get($staff->id),
                'education' => $educations->get($staff->id),
                'dob_raw'   => $dobRaw,
                'dob'       => RetirementCalculator::parseDate($dobRaw),
                'grade_tmt' => $gradeTmt,
                'mkg'       => self::span($gradeTmt, $today),   // masa kerja golongan
                'mks'       => self::span($startDate, $today),  // masa kerja seluruh
                'nip'       => (! empty($nip) && strcasecmp($nip, 'tidak ada') !== 0) ? $nip : '-',
            ];
        }

        usort($entries, function (array $a, array $b) {
            return strnatcasecmp((string) ($b['grade']?->level ?? ''), (string) ($a['grade']?->level ?? ''))
                ?: self::compare($a['grade_tmt']?->getTimestamp(), $b['grade_tmt']?->getTimestamp())
                ?: self::compare($a['mks']['months'] ?? null, $b['mks']['months'] ?? null, desc: true)
                ?: self::compare($a['dob']?->getTimestamp(), $b['dob']?->getTimestamp())
                ?: strcasecmp((string) $a['staff']->name, (string) $b['staff']->name);
        });

        return $entries;
    }

    /**
     * Baris Daftar Urut Kepangkatan siap cetak.
     *
     * @return array<int, array<string, string|int>>
     */
    public static function build(?DateTimeInterface $today = null): array
    {
        $today = $today
            ? DateTimeImmutable::createFromInterface($today)->setTime(0, 0)
            : new DateTimeImmutable('today');

        $result = [];

        foreach (self::ranked($today) as $i => $e) {
            $staff     = $e['staff'];
            $grade     = $e['grade'];
            $position  = $e['position'];
            $education = $e['education'];

            $retirement = RetirementCalculator::calculate(
                $e['dob_raw'],
                $position?->asnPosition?->retirement_age,
                $today
            );

            $result[] = [
                'no'           => $i + 1,
                'name'         => $staff->name_with_title,
                'nip'          => $e['nip'],
                'birth_place'  => self::vault($staff, 'pob') ?: '-',
                'birth_date'   => self::formatDate($e['dob']),
                'grade'        => $grade ? "{$grade->grade_name} ({$grade->grade_code})" : '-',
                'grade_tmt'    => self::formatDate($e['grade_tmt']),
                'position'     => $position?->asnPosition?->name ?? '-',
                'position_tmt' => self::formatDate($position ? RetirementCalculator::parseDate($position->effective_date) : null),
                'mkg'          => $e['mkg']['label'] ?? '-',
                'mks'          => $e['mks']['label'] ?? '-',
                'school'       => $education?->institution_name ?: '-',
                'graduated'    => self::formatDate($education ? RetirementCalculator::parseDate($education->graduation_date) : null),
                'level_major'  => $education
                    ? (implode('/', array_filter([$education->level?->alias, $education->major])) ?: '-')
                    : '-',
                'joined'       => self::formatDate(RetirementCalculator::parseDate($staff->status_effective_date)),
                'retirement'   => self::formatDate($retirement['tmt']),
            ];
        }

        return $result;
    }

    /** Tanggal Indonesia, mis. "1 Januari 2026" (null -> '-'). */
    public static function formatDate(?DateTimeInterface $date): string
    {
        return $date
            ? Carbon::parse($date->format('Y-m-d'))->locale('id')->translatedFormat('j F Y')
            : '-';
    }

    /** Selisih tahun dan bulan dari $from sampai $to (null bila tidak valid). */
    private static function span(?DateTimeImmutable $from, DateTimeImmutable $to): ?array
    {
        if ($from === null || $from > $to) {
            return null;
        }

        $diff = $from->diff($to);

        return [
            'months' => $diff->y * 12 + $diff->m,
            'label'  => sprintf('%d Tahun %d Bulan', $diff->y, $diff->m),
        ];
    }

    /** Pembanding angka; null selalu di akhir. */
    private static function compare(?int $a, ?int $b, bool $desc = false): int
    {
        if ($a === $b) {
            return 0;
        }
        if ($a === null) {
            return 1;
        }
        if ($b === null) {
            return -1;
        }

        return $desc ? $b <=> $a : $a <=> $b;
    }

    private static function vault(Data $staff, string $attribute): ?string
    {
        try {
            return $staff->vault?->{$attribute};
        } catch (Throwable) {
            return null;
        }
    }
}
