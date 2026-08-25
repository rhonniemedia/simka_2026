<?php

namespace App\Http\Controllers\Admin\Personnel;

use App\Http\Controllers\Controller;
use App\Models\Data;
use App\Models\EmploymentStatus;
use App\Models\PersonnelType;
use App\Models\Position;
use Illuminate\Http\Request;

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
        $query = Data::with([
            'vault',
            'personnelType',
            'grade',
            'employmentStatus',
            'highestEducation.level' // <-- Gunakan relasi yang baru
        ]);

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

        // 6. Siapkan Data Opsi untuk Select Filter menggunakan Model
        // Mengubah DB::table menjadi panggilan Model jika memungkinkan, atau pertahankan standar Laravel
        $employmentOptions = EmploymentStatus::pluck('name', 'id') ?? collect();
        $personnelOptions = PersonnelType::pluck('name', 'id') ?? collect();
        $positionOptions = Position::pluck('name', 'id') ?? collect();

        // 6b. Statistik kartu pendidikan
        $stats = $this->getStats();

        // 7. Render view parsial jika request datang dari HTMX
        if ($request->header('HX-Request')) {
            return view('pages.admin.personnel.education.partials._table', compact('staff'));
        }

        // 8. Render halaman utama penuh
        return view('pages.admin.personnel.education.index', array_merge(
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
        // Menghitung statistik pendidikan menggunakan query Eloquent
        // Menggunakan distinct agar jika ada 1 pegawai memiliki 2 riwayat S1, tetap dihitung 1
        $educationStats = Data::join('staff_education_histories', 'staff_data.id', '=', 'staff_education_histories.staff_id')
            ->join('staff_education_levels', 'staff_education_histories.education_level_id', '=', 'staff_education_levels.id')
            ->where('staff_education_histories.verification_status', 'verified')
            ->selectRaw('staff_education_levels.alias, count(distinct staff_data.id) as total')
            ->groupBy('staff_education_levels.id', 'staff_education_levels.alias')
            ->pluck('total', 'alias');

        // Sesuaikan parameter get() dengan isi kolom 'alias' atau 'level' pada tabel staff_education_levels Anda
        return [
            'totalVerified'  => $educationStats->sum(),
            'pascaStats'     => $educationStats->get('s2', 0) + $educationStats->get('s3', 0),
            'sarjanaStats'   => $educationStats->get('s1', 0) + $educationStats->get('d4', 0),
            'menengahStats'  => $educationStats->get('sma', 0) + $educationStats->get('smk', 0) + $educationStats->get('d3', 0),
        ];
    }

    public function destroy(Request $request, $id)
    {
        $staff = Data::findOrFail($id);

        // Asumsi relasi di model Data memiliki cascade delete atau ditangani event
        $staff->delete();

        if ($request->header('HX-Request')) {
            $table = $this->index($request)->render();
            $statsOob = view('pages.admin.personnel.education.partials._stats-cards', array_merge(
                $this->getStats(),
                ['isOob' => true]
            ))->render();

            return $table . $statsOob;
        }

        return $this->index($request);
    }

    public function detailPersonal($id)
    {
        // Mengubah cakupan detail untuk memuat riwayat pendidikan
        $staff = Data::with(['vault', 'educationHistories.level'])->findOrFail($id);
        return view('pages.admin.personnel.education.modals._detail-personal', compact('staff'));
    }

    public function detailEmployment($id)
    {
        $staff = Data::with(['personnelType', 'position'])->findOrFail($id);
        return view('pages.admin.personnel.education.modals._detail-employment', compact('staff'));
    }

    public function editPersonal($id)
    {
        $staff = Data::with(['vault', 'educationHistories'])->findOrFail($id);
        return view('pages.admin.personnel.education.modals._edit-personal', compact('staff'));
    }

    public function show($id)
    {
        // Ambil data pegawai beserta relasi dasar
        $staff = Data::with(['vault', 'employmentStatus'])->findOrFail($id);

        // Paginasi riwayat pendidikan (bukan eager loading collection lagi)
        $educations = $staff->educations()
            ->with('level')
            ->orderBy('graduation_date', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('pages.admin.personnel.education.show.index', compact('staff', 'educations'));
    }
}
