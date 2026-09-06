<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->header('HX-Request')) {
            // Kirim identitas tab langsung dari Controller
            return view('pages.admin.master.index', [
                'activeTab' => 'grades',
                'fetchUrl'  => $request->fullUrl()
            ]);
        }
        // Menggunakan paginate(10) untuk membatasi 10 baris per halaman
        $grades = Grade::orderByRaw('CAST(level AS UNSIGNED) ASC')
            ->paginate(10);
        return view('pages.admin.master.partials.grades', compact('grades'));
    }

    public function create()
    {
        $item = null;
        return view('pages.admin.master.partials._grade-form', compact('item'));
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        Grade::create($validated);

        return $this->respondWithSuccess('Golongan berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $item = Grade::findOrFail($id);
        return view('pages.admin.master.partials._grade-form', compact('item'));
    }

    public function update(Request $request, $id)
    {
        $item = Grade::findOrFail($id);
        $validated = $this->validated($request, $id);

        $item->update($validated);

        return $this->respondWithSuccess('Golongan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $item = Grade::findOrFail($id);
        $item->delete();

        return $this->respondWithSuccess('Golongan berhasil dihapus.');
    }

    /*
    |--------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------
    */

    private function validated(Request $request, ?string $ignoreId = null): array
    {
        return $request->validate([
            'grade_code' => 'required|string|max:50|unique:staff_grades,grade_code' . ($ignoreId ? ",{$ignoreId}" : ''),
            'grade_name' => 'required|string|max:255',
            'level'      => 'required|string|max:50',
        ]);
    }

    private function respondWithSuccess(string $message)
    {
        return response('')
            ->header('HX-Trigger', json_encode([
                'close-modal' => true,
                'refreshGrades' => true, // Trigger untuk memuat ulang tab Golongan
                'showAlert' => [
                    'icon'  => 'success',
                    'title' => 'Berhasil!',
                    'text'  => $message,
                ],
            ]));
    }
}
