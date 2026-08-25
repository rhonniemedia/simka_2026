<?php

namespace App\Http\Controllers\Admin\Payroll;

use App\Http\Controllers\Controller;
use App\Models\PeriodicSalaryHistory;
use Illuminate\Http\Request;

class PeriodicSalaryHistoryController extends Controller
{
    public function index(Request $request)
    {
        // Mengambil data gaji berkala beserta data pegawai terkait
        $histories = PeriodicSalaryHistory::with(['staff.vault', 'staff.highestEducation'])
            ->latest('effective_date')
            ->paginate(10);

        // Jika request dari HTMX, kembalikan partial table saja
        if ($request->header('HX-Request')) {
            return view('pages.admin.personnel.periodic-salary.partials._table', compact('histories'));
        }

        // Jika full page load
        return view('pages.admin.personnel.periodic-salary.index', compact('histories'));
    }
}
