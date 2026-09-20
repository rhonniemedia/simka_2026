<?php

namespace App\Services\Personnel;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Perhitungan pensiun (tanpa akses database, sehingga mudah diuji).
 *
 * Aturan:
 *  - Batas tercapai bila umur >= batas usia jabatan (58 / 60 tahun) pada hari ini.
 *  - TMT pensiun = tanggal 1 pada bulan setelah bulan ulang tahun ke-58/60.
 *
 * Tanggal lahir yang tidak valid diperlakukan sebagai "tidak diketahui"
 * (tidak ditebak).
 */
final class RetirementCalculator
{
    /** Batas usia bawaan bila jabatan ASN pegawai belum ada / belum punya batas usia. */
    public const DEFAULT_AGE = 58;

    /** Pegawai dianggap "segera pensiun" bila batas usia tercapai dalam rentang ini. */
    public const SOON_MONTHS = 12;

    public const STATE_RETIRED = 'retired';
    public const STATE_REACHED = 'reached';
    public const STATE_SOON = 'soon';
    public const STATE_NOT_YET = 'not_yet';
    public const STATE_UNKNOWN = 'unknown';

    /**
     * Ubah nilai menjadi tanggal (jam 00:00). Format yang dikenali: Y-m-d
     * (boleh disertai jam). Nilai kosong / tidak valid / tanggal fiktif
     * (mis. 1968-02-30) menghasilkan null.
     */
    public static function parseDate(mixed $value): ?DateTimeImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value)->setTime(0, 0);
        }

        $value = is_string($value) ? trim($value) : '';
        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', substr($value, 0, 10));
        $errors = DateTimeImmutable::getLastErrors();

        $clean = ! $errors || ($errors['warning_count'] === 0 && $errors['error_count'] === 0);

        return ($date !== false && $clean) ? $date : null;
    }

    /**
     * Tanggal ulang tahun ke-$age. Untuk lahir 29 Februari pada tahun yang
     * tidak kabisat dipakai 28 Februari.
     */
    public static function limitDate(DateTimeImmutable $dob, int $age): DateTimeImmutable
    {
        $year = (int) $dob->format('Y') + $age;
        $month = (int) $dob->format('n');

        $firstOfMonth = $dob->setDate($year, $month, 1)->setTime(0, 0);
        $day = min((int) $dob->format('j'), (int) $firstOfMonth->format('t'));

        return $firstOfMonth->setDate($year, $month, $day);
    }

    /**
     * TMT pensiun: tanggal 1 pada bulan setelah bulan tercapainya batas usia.
     */
    public static function tmtDate(DateTimeImmutable $limitDate): DateTimeImmutable
    {
        return $limitDate->modify('first day of next month')->setTime(0, 0);
    }

    /**
     * @param mixed                  $dob      Tanggal lahir (Y-m-d) atau null.
     * @param int|null               $limitAge Batas usia jabatan; null/<1 memakai DEFAULT_AGE.
     * @param DateTimeInterface|null $today    Tanggal acuan (bawaan: hari ini).
     * @param bool                   $retired  Pegawai sudah berstatus pensiun.
     *
     * @return array{
     *   dob: ?DateTimeImmutable, age: ?int, limit_age: int, used_default_age: bool,
     *   limit_date: ?DateTimeImmutable, tmt: ?DateTimeImmutable, months_left: ?int,
     *   state: string, can_process: bool
     * }
     */
    public static function calculate(
        mixed $dob,
        ?int $limitAge,
        ?DateTimeInterface $today = null,
        bool $retired = false
    ): array {
        $today = $today
            ? DateTimeImmutable::createFromInterface($today)->setTime(0, 0)
            : new DateTimeImmutable('today');

        $usedDefault = $limitAge === null || $limitAge < 1;
        $limitAge = $usedDefault ? self::DEFAULT_AGE : $limitAge;

        $birth = self::parseDate($dob);

        $result = [
            'dob' => $birth,
            'age' => null,
            'limit_age' => $limitAge,
            'used_default_age' => $usedDefault,
            'limit_date' => null,
            'tmt' => null,
            'months_left' => null,
            'state' => $retired ? self::STATE_RETIRED : self::STATE_UNKNOWN,
            'can_process' => false,
        ];

        // Tanggal lahir kosong, tidak valid, atau di masa depan: tidak bisa dihitung.
        if ($birth === null || $birth > $today) {
            return $result;
        }

        $limitDate = self::limitDate($birth, $limitAge);

        $result['age'] = $birth->diff($today)->y;
        $result['limit_date'] = $limitDate;
        $result['tmt'] = self::tmtDate($limitDate);

        if ($retired) {
            return $result;
        }

        if ($today >= $limitDate) {
            $result['state'] = self::STATE_REACHED;
            $result['can_process'] = true;

            return $result;
        }

        $diff = $today->diff($limitDate);
        $result['months_left'] = $diff->y * 12 + $diff->m + ($diff->d > 0 ? 1 : 0);

        $soonEdge = $today->modify('+' . self::SOON_MONTHS . ' months');
        $result['state'] = $limitDate <= $soonEdge ? self::STATE_SOON : self::STATE_NOT_YET;

        return $result;
    }
}
