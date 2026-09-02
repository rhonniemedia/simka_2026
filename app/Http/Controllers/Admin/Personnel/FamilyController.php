<?php

namespace App\Http\Controllers\Admin\Personnel;

use App\Http\Controllers\Controller;
use App\Models\Data as Staff;
use App\Models\Data;
use App\Models\EducationLevel;
use Illuminate\Http\Request;

class FamilyController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $query = Staff::with(['families' => function ($qFamily) {
            $qFamily->whereIn('relationship', ['husband', 'wife']);
        }])
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('families', function ($qFamily) use ($search) {
                        $qFamily->whereIn('relationship', ['husband', 'wife'])
                            ->where('name', 'like', "%{$search}%");
                    });
            })
            ->latest();

        $staffs = $query->paginate(10)->withQueryString();

        if ($request->header('HX-Request')) {
            return view('pages.admin.personnel.family.partials._table', compact('staffs'));
        }

        return view('pages.admin.personnel.family.index', compact('staffs', 'search'));
    }

    public function show(Request $request, $id)
    {
        $staff = Data::with(['vault', 'employmentStatus'])->findOrFail($id);

        $families = $staff->families()
            ->orderByRaw("FIELD(relationship, 'husband', 'wife', 'child', 'other')")
            ->paginate(10);

        if ($request->header('HX-Request')) {
            return view('pages.admin.personnel.family.show.partials._table', compact('staff', 'families'));
        }

        return view('pages.admin.personnel.family.show.index', compact('staff', 'families'));
    }

    public function create($id)
    {
        $staff = Data::findOrFail($id);
        $levels = EducationLevel::orderByRaw('CAST(level AS UNSIGNED) ASC')->get();

        return view('pages.admin.personnel.family.show.partials._modal-form', compact('staff', 'levels'));
    }

    public function store(Request $request, $id)
    {
        $staff = Data::findOrFail($id);

        $validated = $request->validate([
            'name'                    => 'required|string|max:255',
            'relationship'            => 'required|in:husband,wife,child,other',
            'gender'                  => 'required|in:L,P',
            'family_relation_code'    => 'nullable|string|max:50',
            'nik'                     => 'nullable|string|max:50',
            'birth_place_encrypted'   => 'nullable|string|max:255',
            'birth_date'              => 'nullable|date',
            'telephone'               => 'nullable|string|max:255',
            'occupation'              => 'nullable|string|max:255',
            'education_level_id'      => 'nullable|exists:staff_education_levels,id',
            'is_studying'             => 'nullable|boolean',
            'payroll_status'          => 'nullable|in:included,excluded',
            'marriage_date_encrypted' => 'nullable|date',
        ]);

        $staff->families()->create($validated);

        if ($request->header('HX-Request')) {
            return response($this->show($request, $id)->render())
                ->header('HX-Trigger', json_encode([
                    'close-modal' => true,
                    'showAlert' => [
                        'icon'  => 'success',
                        'title' => 'Berhasil!',
                        'text'  => 'Data anggota keluarga berhasil ditambahkan.',
                    ],
                ]));
        }

        return redirect()->route('admin.personnel.family.show', $id);
    }

    public function edit($staff_id, $family_id)
    {
        $staff = Data::findOrFail($staff_id);
        $family = $staff->families()->findOrFail($family_id);
        $levels = EducationLevel::orderByRaw('CAST(level AS UNSIGNED) ASC')->get();

        return view('pages.admin.personnel.family.show.partials._modal-form', compact('staff', 'family', 'levels'));
    }

    public function update(Request $request, $staff_id, $family_id)
    {
        $staff = Data::findOrFail($staff_id);
        $family = $staff->families()->findOrFail($family_id);

        $validated = $request->validate([
            'name'                    => 'required|string|max:255',
            'relationship'            => 'required|in:husband,wife,child,other',
            'gender'                  => 'required|in:L,P',
            'family_relation_code'    => 'nullable|string|max:50',
            'nik'                     => 'nullable|string|max:50',
            'birth_place_encrypted'   => 'nullable|string|max:255',
            'birth_date'              => 'nullable|date',
            'telephone'               => 'nullable|string|max:255',
            'occupation'              => 'nullable|string|max:255',
            'education_level_id'      => 'nullable|exists:staff_education_levels,id',
            'is_studying'             => 'nullable|boolean',
            'payroll_status'          => 'nullable|in:included,excluded',
            'marriage_date_encrypted' => 'nullable|date',
        ]);

        $family->update($validated);

        if ($request->header('HX-Request')) {
            return response($this->show($request, $staff_id)->render())
                ->header('HX-Trigger', json_encode([
                    'close-modal' => true,
                    'showAlert' => [
                        'icon'  => 'success',
                        'title' => 'Berhasil!',
                        'text'  => 'Data anggota keluarga berhasil diperbarui.',
                    ],
                ]));
        }

        return redirect()->route('admin.personnel.family.show', $staff_id);
    }

    public function destroy(Request $request, $staff_id, $family_id)
    {
        try {
            $staff = Data::findOrFail($staff_id);
            $family = $staff->families()->findOrFail($family_id);
            $family->delete();
        } catch (\Throwable $e) {
            report($e);

            if ($request->header('HX-Request')) {
                return response($this->show($request, $staff_id)->render())
                    ->header('HX-Trigger', json_encode([
                        'showAlert' => [
                            'icon'  => 'error',
                            'title' => 'Gagal!',
                            'text'  => 'Data keluarga gagal dihapus. Silakan coba lagi.',
                        ],
                    ]));
            }
            return redirect()->route('admin.personnel.family.show', $staff_id);
        }

        if ($request->header('HX-Request')) {
            return response($this->show($request, $staff_id)->render())
                ->header('HX-Trigger', json_encode([
                    'showAlert' => [
                        'icon'  => 'success',
                        'title' => 'Dihapus!',
                        'text'  => 'Data keluarga berhasil dihapus.',
                    ],
                ]));
        }

        return redirect()->route('admin.personnel.family.show', $staff_id);
    }
}
