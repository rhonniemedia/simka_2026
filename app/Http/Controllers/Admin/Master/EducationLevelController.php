<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Models\EducationLevel;
use Illuminate\Http\Request;

class EducationLevelController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->header('HX-Request')) {
            // Kirim identitas tab langsung dari Controller
            return view('pages.admin.master.index', [
                'activeTab' => 'education_levels',
                'fetchUrl'  => $request->fullUrl()
            ]);
        }

        // Mengurutkan berdasarkan level dan menerapkan paginasi
        $educationLevels = EducationLevel::orderByRaw('CAST(level AS UNSIGNED) ASC')
            ->paginate(10);

        return view('pages.admin.master.partials.education-levels', compact('educationLevels'));
    }

    public function create()
    {
        $item = null;
        return view('pages.admin.master.partials._education-level-form', compact('item'));
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        EducationLevel::create($validated);

        return $this->respondWithSuccess('Tingkat Pendidikan berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $item = EducationLevel::findOrFail($id);
        return view('pages.admin.master.partials._education-level-form', compact('item'));
    }

    public function update(Request $request, $id)
    {
        $item = EducationLevel::findOrFail($id);
        $validated = $this->validated($request, $id);

        $item->update($validated);

        return $this->respondWithSuccess('Tingkat Pendidikan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $item = EducationLevel::findOrFail($id);
        $item->delete();

        return $this->respondWithSuccess('Tingkat Pendidikan berhasil dihapus.');
    }

    /*
    |--------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------
    */

    private function validated(Request $request, ?string $ignoreId = null): array
    {
        return $request->validate([
            'name'  => 'required|string|max:255|unique:staff_education_levels,name' . ($ignoreId ? ",{$ignoreId}" : ''),
            'alias' => 'required|string|max:50',
            'level' => 'required|numeric',
        ]);
    }

    private function respondWithSuccess(string $message)
    {
        return response('')
            ->header('HX-Trigger', json_encode([
                'close-modal' => true,
                'refreshEducationLevels' => true, // Trigger untuk memuat ulang tab
                'showAlert' => [
                    'icon'  => 'success',
                    'title' => 'Berhasil!',
                    'text'  => $message,
                ],
            ]));
    }
}
