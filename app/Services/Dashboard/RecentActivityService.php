<?php

namespace App\Services\Dashboard;

use App\Http\Controllers\Admin\Career\TransferController;
use App\Models\AsnPositionHistory;
use App\Models\Data;
use App\Models\EducationHistory;
use App\Models\FamilyRelation;
use App\Models\GradeHistory;
use App\Models\PeriodicSalaryHistory;
use App\Models\StaffStatusHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * Menggabungkan aktivitas terbaru dari 7 modul menjadi satu feed.
 *
 * Tiap item punya bentuk yang sama:
 *   type, name, gender, date (Carbon), by,
 *   from + to        -> perubahan "lama → baru"
 *   title + subtitle -> detail teks untuk penambahan data
 *
 * Tanggal aktivitas = created_at (kapan data ditambahkan), bukan TMT.
 */
class RecentActivityService
{
    private const RELATION_LABELS = [
        'husband' => 'Suami',
        'wife'    => 'Istri',
        'child'   => 'Anak',
        'other'   => 'Lainnya',
    ];

    public function get(int $limit = 10): Collection
    {
        // Ambil $limit teratas dari tiap sumber, gabung, lalu potong lagi.
        $items = collect()
            ->merge($this->safely(fn() => $this->families($limit)))
            ->merge($this->safely(fn() => $this->educations($limit)))
            ->merge($this->safely(fn() => $this->salaries($limit)))
            ->merge($this->safely(fn() => $this->grades($limit)))
            ->merge($this->safely(fn() => $this->positions($limit)))
            ->merge($this->safely(fn() => $this->statuses($limit)))
            ->sortByDesc('date')
            ->take($limit)
            ->values();

        // Satu query untuk semua pembuat beserta role-nya.
        $creators = User::with('roles')
            ->whereIn('id', $items->pluck('created_by')->filter()->unique())
            ->get()
            ->keyBy('id');

        // Satu query untuk semua pegawai (tanpa bergantung nama relasi di tiap model).
        $staffById = Data::whereIn('id', $items->pluck('staff_id')->filter()->unique())->get()->keyBy('id');

        return $items->map(function (array $item) use ($creators, $staffById) {
            $staff = $staffById->get($item['staff_id']);

            return Arr::except($item + [
                'name'   => $staff?->name ?? 'Pegawai tidak ditemukan',
                'gender' => $staff?->gender,
                'by'     => $this->creatorLabel($creators->get($item['created_by'])),
            ], ['created_by', 'staff_id']);
        });
    }

    /* ------------------------------------------------------------------ */
    /*  Sumber data                                                        */
    /* ------------------------------------------------------------------ */

    private function families(int $limit): Collection
    {
        return FamilyRelation::with('familyMember')
            ->latest()->limit($limit)->get()
            ->map(function ($r) {
                $key = $r->relationship instanceof \BackedEnum ? $r->relationship->value : (string) $r->relationship;
                $label = self::RELATION_LABELS[$key] ?? ucfirst($key);

                return $this->item('keluarga', $r, [
                    'title' => $label . ': ' . ($r->familyMember?->name ?? '-'),
                ]);
            });
    }

    private function educations(int $limit): Collection
    {
        return EducationHistory::with('level')
            ->latest()->limit($limit)->get()
            ->map(function ($e) {
                $year = $e->graduation_date ? Carbon::parse($e->graduation_date)->format('Y') : null;

                return $this->item('pendidikan', $e, [
                    'title'    => trim(($e->level?->alias ?? '') . ' ' . ($e->major ?? '')) ?: '-',
                    'subtitle' => implode(', ', array_filter([$e->institution_name, $year])) ?: null,
                ]);
            });
    }

    private function salaries(int $limit): Collection
    {
        return PeriodicSalaryHistory::query()
            ->latest()->limit($limit)->get()
            ->map(function ($h) {
                $to = $this->rupiah($h->base_salary);
                // base_salary adalah accessor (dekripsi base_salary_encrypted), jadi harus lewat model.
                $from = $this->rupiah(
                    PeriodicSalaryHistory::where('staff_id', $h->staff_id)
                        ->where('effective_date', '<', $h->effective_date)
                        ->orderByDesc('effective_date')
                        ->first()?->base_salary
                );
                $tmt = 'TMT ' . $this->date($h->effective_date);

                if ($to && $from) {
                    return $this->item('gaji', $h, ['from' => $from, 'to' => $to]);
                }

                return $this->item('gaji', $h, [
                    'title'    => $to ?? 'Kenaikan gaji berkala',
                    'subtitle' => $tmt,
                ]);
            });
    }

    private function grades(int $limit): Collection
    {
        return GradeHistory::with('grade')
            ->latest()->limit($limit)->get()
            ->map(function ($h) {
                $to = $this->gradeLabel($h->grade);
                $from = $this->gradeLabel(
                    GradeHistory::with('grade')
                        ->where('staff_id', $h->staff_id)
                        ->where('effective_date', '<', $h->effective_date)
                        ->orderByDesc('effective_date')
                        ->first()?->grade
                );

                return $from
                    ? $this->item('pangkat', $h, ['from' => $from, 'to' => $to])
                    : $this->item('pangkat', $h, ['title' => $to, 'subtitle' => 'TMT ' . $this->date($h->effective_date)]);
            });
    }

    private function positions(int $limit): Collection
    {
        return AsnPositionHistory::with('asnPosition')
            ->latest()->limit($limit)->get()
            ->map(function ($h) {
                $to = $h->asnPosition?->name ?? '-';
                $from = AsnPositionHistory::with('asnPosition')
                    ->where('staff_id', $h->staff_id)
                    ->where('effective_date', '<', $h->effective_date)
                    ->orderByDesc('effective_date')
                    ->first()?->asnPosition?->name;

                return $from
                    ? $this->item('jabatan', $h, ['from' => $from, 'to' => $to])
                    : $this->item('jabatan', $h, ['title' => $to, 'subtitle' => 'TMT ' . $this->date($h->effective_date)]);
            });
    }

    private function statuses(int $limit): Collection
    {
        $labels = TransferController::STATUS_LABELS;

        return StaffStatusHistory::query()
            ->latest()->limit($limit)->get()
            ->map(fn($h) => $this->item(
                $h->to_status === 'retired' ? 'pensiun' : 'mutasi',
                $h,
                [
                    'from' => $labels[$h->from_status] ?? $h->from_status,
                    'to'   => $labels[$h->to_status] ?? $h->to_status,
                ]
            ));
    }

    /* ------------------------------------------------------------------ */
    /*  Helper                                                             */
    /* ------------------------------------------------------------------ */

    /** Admin & superadmin ditampilkan sebagai "Admin"; selain itu username-nya. */
    private function creatorLabel(?User $user): string
    {
        if (! $user) {
            return '-';
        }

        $isAdmin = $user->roles
            ->pluck('name')
            ->map(fn($name) => preg_replace('/[\s_-]+/', '', strtolower((string) $name)))
            ->intersect(['admin', 'superadmin'])
            ->isNotEmpty();

        return $isAdmin ? 'Admin' : ($user->username ?: '-');
    }

    /** Jalankan satu sumber; kalau gagal, catat ke log dan lewati sumber itu saja. */
    private function safely(callable $source): Collection
    {
        try {
            return $source();
        } catch (\Throwable $e) {
            report($e);

            return collect();
        }
    }

    private function item(string $type, $record, array $extra = []): array
    {
        return array_merge([
            'type'       => $type,
            'staff_id'   => $record->staff_id,
            'date'       => $record->created_at,
            'created_by' => $record->created_by,
            'from'       => null,
            'to'         => null,
            'title'      => null,
            'subtitle'   => null,
        ], $extra);
    }

    private function rupiah($value): ?string
    {
        return $value === null ? null : 'Rp ' . number_format((float) $value, 0, ',', '.');
    }

    private function gradeLabel($grade): ?string
    {
        if (! $grade) {
            return null;
        }

        $label = trim(($grade->grade_name ?? '') . (filled($grade->grade_code) ? ' (' . $grade->grade_code . ')' : ''));

        return $label !== '' ? $label : null;
    }

    private function date($value): string
    {
        return Carbon::parse($value)->translatedFormat('d M Y');
    }
}
