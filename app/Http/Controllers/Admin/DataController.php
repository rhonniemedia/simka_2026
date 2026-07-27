<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Data;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataController extends Controller
{
    public function index(Request $request)
    {
        // 1. Ambil parameter dari request
        $search = $request->input('search');
        $filterPersonnel = $request->input('filter_personnel');
        $filterPosition = $request->input('filter_position');
        $filterGender = $request->input('filter_gender');
        $filterStatus = $request->input('filter_status');

        // 2. Inisiasi Query beserta relasinya
        $query = Data::with(['vault', 'personnelType', 'grade']);

        // 3. Logika Pencarian (Nama & Hash NIK/NIP/NUPTK)
        if (!empty($search)) {
            // Karena NIK, NIP, NUPTK disimpan dengan hash('sha256', trim($value)) di DataVault
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
        if (!empty($filterPersonnel)) {
            $query->where('personnel_id', $filterPersonnel);
        }

        if (!empty($filterPosition)) {
            $query->where('position_id', $filterPosition);
        }

        if (!empty($filterGender)) {
            $query->where('gender', $filterGender);
        }

        if (!empty($filterStatus)) {
            $query->where('status', $filterStatus);
        }

        // 5. Eksekusi Paginasi
        $staff = $query->latest()->paginate(10)->withQueryString();

        // 6. Siapkan Data Opsi untuk Select Filter (Silakan sesuaikan modelnya jika berbeda)
        // Opsi ini bisa diambil dari tabel referensi master jabatan dan jenis pegawai
        $personnelOptions = DB::table('staff_personnel_types')->pluck('name', 'id');
        $positionOptions = DB::table('staff_positions')->pluck('name', 'id');

        // 7. Render view parsial jika request datang dari HTMX (pencarian/filter/paginasi tanpa reload)
        if ($request->header('HX-Request')) {
            return view('pages.admin.staff.data.partials._table-container', compact(
                'staff'
            ));
        }

        // 8. Render halaman utama penuh
        return view('pages.admin.staff.data.index', compact(
            'staff',
            'search',
            'filterPersonnel',
            'filterPosition',
            'filterGender',
            'filterStatus',
            'personnelOptions',
            'positionOptions'
        ));
    }

    public function destroy(Request $request, $id)
    {
        $staff = Data::findOrFail($id);

        // Proses hapus. Karena constrained cascade di migration, vault otomatis terhapus
        $staff->delete();

        // Mengembalikan view tabel terbaru via HTMX setelah penghapusan
        return $this->index($request);
    }

    // =========================================================================
    // Fungsi Placeholder untuk merender modal via HTMX
    // =========================================================================

    public function detailPersonal($id)
    {
        $staff = Data::with(['vault'])->findOrFail($id);
        return view('pages.admin.staff.data.modals._detail-personal', compact('staff'));
    }

    public function detailEmployment($id)
    {
        $staff = Data::with(['personnelType', 'position'])->findOrFail($id);
        return view('pages.admin.staff.data.modals._detail-employment', compact('staff'));
    }

    public function editPersonal($id)
    {
        $staff = Data::with(['vault'])->findOrFail($id);
        return view('pages.admin.staff.data.modals._edit-personal', compact('staff'));
    }
}
