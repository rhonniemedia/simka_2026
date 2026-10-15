<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AsnPositionHistory;
use App\Models\Data;
use App\Models\PeriodicSalaryHistory;
use App\Models\StaffStatusHistory;
use App\Services\Personnel\RetirementList;
use Carbon\Carbon;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class DashboardController extends Controller
{
    /** Status kepegawaian (slug) yang dihitung: PNS, PPPK, dan PPPK Paruh Waktu. Samakan dengan modul Mutasi & Pensiun. */
    private const INCLUDED_EMPLOYMENT_SLUGS = ['pns', 'pppk', 'pppkpw'];

    /** Jumlah baris pada tiap daftar tunggu di dashboard. */
    private const WAITLIST_LIMIT = 5;

    /** Jarak antar kenaikan gaji berkala (tahun). */
    private const PERIODIC_INTERVAL_YEARS = 2;

    /** Pegawai masuk daftar tunggu jika jatuh tempo <= sekian hari lagi (yang sudah lewat tetap tampil). */
    private const PERIODIC_HORIZON_DAYS = 90;

    /** Batas masa kerja (tahun) - sama dengan aturan di PeriodicSalaryHistoryController. */
    private const PERIODIC_MAX_SERVICE_YEARS = 31;

    public function index(Request $request)
    {
        $activeStaffCount = Data::completed()->where('status', 'active')->count();

        $activePositionCount = AsnPositionHistory::where('is_active', true)->count();

        $mutationThisYearCount = StaffStatusHistory::whereYear('effective_date', now()->year)->count();

        // Satu kali hitung untuk kartu "Segera Pensiun" sekaligus daftar tunggu pensiun.
        $retirementCandidates = $this->retirementCandidates();
        $retirementSoonCount  = $retirementCandidates->count();
        $retirementWaitlist   = $retirementCandidates->take(self::WAITLIST_LIMIT)->values();

        $periodicWaitlist = $this->periodicWaitlist();

        $employmentBreakdown = Data::completed()
            ->where('staff_data.status', 'active')
            ->join('staff_employment_statuses', 'staff_employment_statuses.id', '=', 'staff_data.employment_id')
            ->whereIn('staff_employment_statuses.slug', self::INCLUDED_EMPLOYMENT_SLUGS)
            ->select('staff_employment_statuses.name', DB::raw('count(*) as total'))
            ->groupBy('staff_employment_statuses.id', 'staff_employment_statuses.name', 'staff_employment_statuses.code')
            ->orderBy('staff_employment_statuses.code')
            ->get();

        $recentActivities = StaffStatusHistory::with(['staff', 'creator'])
            ->latest('effective_date')
            ->latest('created_at')
            ->limit(8)
            ->get();

        return view('pages.admin.dashboard.index', compact(
            'activeStaffCount',
            'activePositionCount',
            'mutationThisYearCount',
            'retirementSoonCount',
            'retirementWaitlist',
            'periodicWaitlist',
            'employmentBreakdown',
            'recentActivities'
        ));
    }

    /**
     * Pegawai dengan status pensiun 'reached' (sudah lewat batas usia, belum
     * diproses) atau 'soon' (segera pensiun). Perhitungan sama seperti
     * RetirementController::buildRows(), karena umur dihitung di PHP dari
     * tanggal lahir yang terenkripsi (tidak bisa lewat SQL).
     *
     * Sudah terurut seperti halaman Pensiun (RetirementList::sort): yang sudah
     * melewati batas usia di paling atas, lalu batas usia terdekat.
     *
     * Tiap item: ->staff (model Data) dan ->due_date (TMT pensiun; jika belum
     * ada, pakai tanggal batas usia).
     *
     * @return Collection<int, object>
     */
    private function retirementCandidates(): Collection
    {
        $staffList = Data::completed()
            ->whereIn('staff_data.status', ['active', 'retired'])
            ->whereHas('employmentStatus', fn($q) => $q->whereIn('slug', self::INCLUDED_EMPLOYMENT_SLUGS))
            ->with(['vault', 'employmentStatus'])
            ->get();

        if ($staffList->isEmpty()) {
            return collect();
        }

        $activePositions = AsnPositionHistory::with('asnPosition')
            ->whereIn('staff_id', $staffList->pluck('id'))
            ->where('is_active', true)
            ->orderByDesc('effective_date')
            ->get()
            ->unique('staff_id')
            ->keyBy('staff_id');

        $today = new DateTimeImmutable('today');
        $rows = [];

        foreach ($staffList as $staff) {
            $row = RetirementList::makeRow(
                $staff,
                $activePositions->get($staff->id)?->asnPosition,
                $this->readDob($staff),
                $today
            );

            if (! in_array($row['calc']['state'], ['reached', 'soon'], true)) {
                continue;
            }

            $rows[] = $row;
        }

        return collect(RetirementList::sort($rows))->map(fn(array $row) => (object) [
            'staff'    => $row['staff'],
            'due_date' => $row['calc']['tmt'] ?? $row['calc']['limit_date'] ?? null,
        ]);
    }

    /**
     * Daftar tunggu kenaikan gaji berkala (KGB).
     * Jatuh tempo = TMT riwayat berkala terakhir + 2 tahun (aturan sama dengan
     * halaman Gaji Berkala). Pegawai yang masa kerjanya sudah melewati 31 tahun
     * pada TMT terakhir tidak dihitung lagi. Pegawai tanpa riwayat berkala
     * tidak ikut karena tanggal acuannya tidak ada.
     *
     * Tiap item: ->staff (model Data) dan ->due_date (Y-m-d). Terurut dari
     * jatuh tempo paling awal (yang sudah terlewat di paling atas).
     *
     * @return Collection<int, object>
     */
    private function periodicWaitlist(): Collection
    {
        $cutoff = Carbon::today()
            ->addDays(self::PERIODIC_HORIZON_DAYS)
            ->subYears(self::PERIODIC_INTERVAL_YEARS)
            ->toDateString();

        // TMT terakhir per pegawai, difilter langsung di SQL
        // (hanya yang sudah / hampir jatuh tempo).
        $lastTmtByStaff = PeriodicSalaryHistory::query()
            ->select('staff_id', DB::raw('MAX(effective_date) as last_tmt'))
            ->groupBy('staff_id')
            ->havingRaw('MAX(effective_date) <= ?', [$cutoff])
            ->pluck('last_tmt', 'staff_id');

        if ($lastTmtByStaff->isEmpty()) {
            return collect();
        }

        $staffList = Data::completed()
            ->where('staff_data.status', 'active')
            ->whereIn('staff_data.id', $lastTmtByStaff->keys()->all())
            ->whereHas('employmentStatus', fn($q) => $q->whereIn('slug', self::INCLUDED_EMPLOYMENT_SLUGS))
            ->get();

        return $staffList
            ->map(function (Data $staff) use ($lastTmtByStaff) {
                $lastTmt = Carbon::parse($lastTmtByStaff[$staff->id])->startOfDay();
                $start   = $this->readServiceStart($staff);

                if ($start) {
                    $years = (int) floor($start->diffInMonths($lastTmt) / 12);
                    if ($years > self::PERIODIC_MAX_SERVICE_YEARS) {
                        return null;
                    }
                }

                return (object) [
                    'staff'    => $staff,
                    'due_date' => $lastTmt->copy()->addYears(self::PERIODIC_INTERVAL_YEARS)->toDateString(),
                ];
            })
            ->filter()
            ->sortBy('due_date')
            ->values();
    }

    private function readServiceStart(Data $staff): ?Carbon
    {
        try {
            $date = $staff->prior_service_period_effective_date;

            return $date ? Carbon::parse($date) : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function readDob(Data $staff): ?string
    {
        try {
            return $staff->vault?->dob;
        } catch (Throwable) {
            return null;
        }
    }
}
