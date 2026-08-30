<?php

namespace App\Http\Controllers\Admin\Personnel;

use App\Http\Controllers\Controller;
use App\Models\Data as Staff;
use App\Models\Data;
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
}
