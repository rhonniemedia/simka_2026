<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Models\PersonnelType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PersonnelTypeController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->header('HX-Request')) {
            // Kirim identitas tab langsung dari Controller
            return view('pages.admin.master.index', [
                'activeTab' => 'personnel_types',
                'fetchUrl'  => $request->fullUrl()
            ]);
        }

        // Mengambil data jenis personel, diurutkan berdasarkan code
        $personnelTypes = PersonnelType::orderBy('code')->get();

        // Merender view partial (tanpa layout utama)
        return view('pages.admin.master.partials.personnel-types', compact('personnelTypes'));
    }

    public function create()
    {
        $item = null;

        return view('pages.admin.master.partials._personnel-type-form', compact('item'));
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $validated['slug'] = $this->generateUniqueSlug($validated['name']);

        PersonnelType::create($validated);

        return $this->respondWithSuccess('Jenis personel berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $item = PersonnelType::findOrFail($id);

        return view('pages.admin.master.partials._personnel-type-form', compact('item'));
    }

    public function update(Request $request, $id)
    {
        $item = PersonnelType::findOrFail($id);
        $validated = $this->validated($request, $id);

        $item->update($validated);

        return $this->respondWithSuccess('Jenis personel berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $item = PersonnelType::findOrFail($id);
        $item->delete();

        return $this->respondWithSuccess('Jenis personel berhasil dihapus.');
    }

    /*
    |--------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------
    */

    private function validated(Request $request, ?string $ignoreId = null): array
    {
        return $request->validate([
            'code'  => 'required|string|max:50|unique:staff_personnel_types,code' . ($ignoreId ? ",{$ignoreId}" : ''),
            'name'  => 'required|string|max:255',
            'alias' => 'nullable|string|max:255',
        ]);
    }

    private function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base !== '' ? $base : 'jenis-personel';
        $i = 1;

        while (PersonnelType::where('slug', $slug)->exists()) {
            $i++;
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    /**
     * Container tabel (lihat personnel-types.blade.php) sudah pasang
     * hx-trigger="refreshPersonnelTypes from:body" pada dirinya sendiri -
     * jadi cukup kirim event ini lewat HX-Trigger, container akan
     * refresh sendiri tanpa perlu kita render ulang tabelnya manual.
     */
    private function respondWithSuccess(string $message)
    {
        return response('')
            ->header('HX-Trigger', json_encode([
                'close-modal' => true,
                'refreshPersonnelTypes' => true,
                'showAlert' => [
                    'icon'  => 'success',
                    'title' => 'Berhasil!',
                    'text'  => $message,
                ],
            ]));
    }
}
