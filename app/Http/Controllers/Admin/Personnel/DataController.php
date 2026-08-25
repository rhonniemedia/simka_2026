<?php

namespace App\Http\Controllers\Admin\Personnel;

use App\Http\Controllers\Controller;
use App\Models\Data;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataController extends Controller
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
            return view('pages.admin.personnel.data.partials._table', compact('staff'));
        }

        // 8. Render halaman utama penuh
        return view('pages.admin.personnel.data.index', array_merge(
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
        // Ubah referensi 'data' menjadi 'staff_data' sesuai dengan nama tabel di database
        $statuses = Data::leftJoin('staff_employment_statuses', 'staff_data.employment_id', '=', 'staff_employment_statuses.id')
            ->selectRaw('staff_employment_statuses.slug, count(staff_data.id) as total')
            ->groupBy('staff_employment_statuses.id', 'staff_employment_statuses.slug')
            ->pluck('total', 'slug');

        // Mengambil data berdasarkan slug 'pppk' dan 'pppk-pw'
        $pppk = $statuses->get('pppk', 0);
        $pppkPw = $statuses->get('pppk-pw', 0);

        return [
            'totalStats'   => Data::count(),
            'pnsStats'     => $statuses->get('pns', 0),
            'pppkTotal'    => $pppk + $pppkPw,
            'pppkPenuh'    => $pppk,
            'pppkParuh'    => $pppkPw,
            'honorerStats' => $statuses->get('honorer', 0),
        ];
    }

    public function destroy(Request $request, $id)
    {
        $staff = Data::findOrFail($id);
        $staff->delete();

        if ($request->header('HX-Request')) {
            $table = $this->index($request)->render();
            $statsOob = view('pages.admin.personnel.data.partials._stats-cards', array_merge(
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
        return view('pages.admin.personnel.data.modals._detail-personal', compact('staff'));
    }

    public function detailEmployment($id)
    {
        $staff = Data::with(['personnelType', 'position'])->findOrFail($id);
        return view('pages.admin.personnel.data.modals._detail-employment', compact('staff'));
    }

    public function editPersonal($id)
    {
        $staff = Data::with(['vault'])->findOrFail($id);
        return view('pages.admin.personnel.data.modals._edit-personal', compact('staff'));
    }
}
