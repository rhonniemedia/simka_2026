<?php

namespace App\Http\Controllers\Admin\Personnel;

use App\Http\Controllers\Controller;
use App\Models\Data;
use App\Models\GradeHistory;
use Illuminate\Http\Request;

class GradeHistoryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        // Mengambil data riwayat pangkat beserta relasi pegawai dan master golongan
        $query = GradeHistory::with(['staff', 'grade'])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('staff', function ($qStaff) use ($search) {
                    $qStaff->where('name', 'like', "%{$search}%");
                })
                    ->orWhereHas('grade', function ($qGrade) use ($search) {
                        $qGrade->where('name', 'like', "%{$search}%");
                    });
            })
            ->orderBy('effective_date', 'desc');

        $histories = $query->paginate(10)->withQueryString();

        // Jika request dari HTMX (pencarian/pagination), kembalikan partial table saja
        if ($request->header('HX-Request')) {
            return view('pages.admin.personnel.grade.partials._table', compact('histories'));
        }

        return view('pages.admin.personnel.grade.index', compact('histories', 'search'));
    }

    public function show(Request $request, $id)
    {
        // 1. Ambil data pegawai beserta relasinya
        $staff = Data::with(['vault', 'employmentStatus'])->findOrFail($id);

        // 2. Ambil data riwayat kepangkatan khusus untuk pegawai ini
        $histories = GradeHistory::with(['grade'])
            ->where('staff_id', $id)
            ->orderBy('effective_date', 'desc')
            ->paginate(10);

        // 3. Jika request dari HTMX, kembalikan partial tabel show-nya
        if ($request->header('HX-Request')) {
            return view('pages.admin.personnel.grade.show.partials._table', compact('staff', 'histories'));
        }

        // 4. Kembalikan view utama show
        return view('pages.admin.personnel.grade.show.index', compact('staff', 'histories'));
    }
}
