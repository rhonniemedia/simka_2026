<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AsnPositionHistory;
use App\Models\Data;
use App\Models\StaffStatusHistory;
use App\Services\Personnel\RetirementList;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class DashboardController extends Controller
{
    /** Status kepegawaian (slug) yang dihitung: PNS, PPPK, dan PPPK Paruh Waktu. Samakan dengan modul Mutasi & Pensiun. */
    private const INCLUDED_EMPLOYMENT_SLUGS = ['pns', 'pppk', 'pppkpw'];

    public function index(Request $request)
    {
        $activeStaffCount = Data::completed()->where('status', 'active')->count();

        $activePositionCount = AsnPositionHistory::where('is_active', true)->count();

        $mutationThisYearCount = StaffStatusHistory::whereYear('effective_date', now()->year)->count();

        $retirementSoonCount = $this->countRetirementSoon();

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
            'employmentBreakdown',
            'recentActivities'
        ));
    }

    /**
     * Jumlah pegawai dengan status pensiun 'reached' (sudah lewat batas usia,
     * belum diproses) atau 'soon' (segera pensiun). Perhitungan dilakukan
     * sama seperti RetirementController::buildRows(), karena umur dihitung
     * di PHP dari tanggal lahir yang terenkripsi (tidak bisa lewat SQL).
     */
    private function countRetirementSoon(): int
    {
        $staffList = Data::completed()
            ->whereIn('staff_data.status', ['active', 'retired'])
            ->whereHas('employmentStatus', fn($q) => $q->whereIn('slug', self::INCLUDED_EMPLOYMENT_SLUGS))
            ->with(['vault', 'employmentStatus'])
            ->get();

        if ($staffList->isEmpty()) {
            return 0;
        }

        $activePositions = AsnPositionHistory::with('asnPosition')
            ->whereIn('staff_id', $staffList->pluck('id'))
            ->where('is_active', true)
            ->orderByDesc('effective_date')
            ->get()
            ->unique('staff_id')
            ->keyBy('staff_id');

        $today = new DateTimeImmutable('today');
        $count = 0;

        foreach ($staffList as $staff) {
            $row = RetirementList::makeRow(
                $staff,
                $activePositions->get($staff->id)?->asnPosition,
                $this->readDob($staff),
                $today
            );

            if (in_array($row['calc']['state'], ['reached', 'soon'], true)) {
                $count++;
            }
        }

        return $count;
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
