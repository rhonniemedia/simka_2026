<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Data;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EducationHistoryController extends Controller
{
    public function index(Request $request)
    {
        // 1. Ambil parameter dari request
        $filterEmploymentStatus = $request->input('filter_employment_status');
        $filterPersonnel = $request->input('filter_personnel');
        $filterPosition = $request->input('filter_position');
        $filterGender = $request->input('filter_gender');
        $search = $request->input('search');

        // 2. Inisiasi Query beserta relasinya
        $query = Data::with(['vault', 'personnelType', 'grade', 'employmentStatus']);

        // 3. Logika Pencarian (Nama & Hash NIK/NIP/NUPTK)
        if (!empty($search)) {
            $searchHash = hash('sha256', trim($search));

            $query->where(function ($q) use ($search, $searchHash) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('vault', function ($qVault) use ($searchHash) {
                        $qVault->where('nik_hash', $searchHash)
                            ->orWhere('nip_hash', $searchHash)
                            ->orWhere('nuptk_hash', $searchHash);
                    });
            });
        }

        // 4. Logika Filter
        if (!empty($filterEmploymentStatus)) {
            $query->where('employment_id', $filterEmploymentStatus);
        }

        if (!empty($filterPersonnel)) {
            $query->where('personnel_id', $filterPersonnel);
        }

        if (!empty($filterPosition)) {
            $query->where('position_id', $filterPosition);
        }

        if (!empty($filterGender)) {
            $query->where('gender', $filterGender);
        }

        // 5. Eksekusi Paginasi (Berdasarkan abjad)
        $staff = $query->orderBy('name', 'asc')->paginate(10)->withQueryString();

        // 6. Siapkan Data Opsi untuk Select Filter
        $employmentOptions = DB::table('staff_employment_statuses')->pluck('name', 'id');
        $personnelOptions = DB::table('staff_personnel_types')->pluck('name', 'id');
        $positionOptions = DB::table('staff_positions')->pluck('name', 'id');

        // 6b. Statistik kartu
        $stats = $this->getStats();

        // 7. Render view parsial jika request datang dari HTMX
        if ($request->header('HX-Request')) {
            return view('pages.admin.staff.education.partials._table', compact('staff'));
        }

        // 8. Render halaman utama penuh
        return view('pages.admin.staff.education.index', array_merge(
            compact(
                'staff',
                'search',
                'filterEmploymentStatus',
                'filterPersonnel',
                'filterPosition',
                'filterGender',
                'employmentOptions',
                'personnelOptions',
                'positionOptions'
            ),
            $stats
        ));
    }

    private function getStats(): array
    {
        $counts = Data::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'totalStats'    => $counts->sum(),
            'activeStats'   => $counts->get('active', 0),
            'inactiveStats' => $counts->get('inactive', 0),
            'retiredStats'  => $counts->get('retired', 0),
            'resignedStats' => $counts->get('resigned', 0),
        ];
    }

    public function destroy(Request $request, $id)
    {
        $staff = Data::findOrFail($id);
        $staff->delete();

        if ($request->header('HX-Request')) {
            $table = $this->index($request)->render();
            $statsOob = view('pages.admin.staff.education.partials._stats-cards', array_merge(
                $this->getStats(),
                ['isOob' => true]
            ))->render();

            return $table . $statsOob;
        }

        return $this->index($request);
    }

    public function detailPersonal($id)
    {
        $staff = Data::with(['vault'])->findOrFail($id);
        return view('pages.admin.staff.education.modals._detail-personal', compact('staff'));
    }

    public function detailEmployment($id)
    {
        $staff = Data::with(['personnelType', 'position'])->findOrFail($id);
        return view('pages.admin.staff.education.modals._detail-employment', compact('staff'));
    }

    public function editPersonal($id)
    {
        $staff = Data::with(['vault'])->findOrFail($id);
        return view('pages.admin.staff.education.modals._edit-personal', compact('staff'));
    }
}
