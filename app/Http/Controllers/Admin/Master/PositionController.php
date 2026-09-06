<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->header('HX-Request')) {
            // Kirim identitas tab langsung dari Controller
            return view('pages.admin.master.index', [
                'activeTab' => 'positions',
                'fetchUrl'  => $request->fullUrl()
            ]);
        }

        // Mengurutkan berdasarkan yang terbaru dan menerapkan paginasi
        $positions = Position::orderByRaw('CAST(code AS UNSIGNED) ASC')
            ->paginate(10);

        return view('pages.admin.master.partials.positions', compact('positions'));
    }

    public function create()
    {
        $item = null;
        return view('pages.admin.master.partials._position-form', compact('item'));
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        Position::create($validated);

        return $this->respondWithSuccess('Jabatan berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $item = Position::findOrFail($id);
        return view('pages.admin.master.partials._position-form', compact('item'));
    }

    public function update(Request $request, $id)
    {
        $item = Position::findOrFail($id);
        $validated = $this->validated($request, $id);

        $item->update($validated);

        return $this->respondWithSuccess('Jabatan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $item = Position::findOrFail($id);
        $item->delete();

        return $this->respondWithSuccess('Jabatan berhasil dihapus.');
    }

    /*
    |--------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------
    */

    private function validated(Request $request, ?string $ignoreId = null): array
    {
        return $request->validate([
            'code' => 'required|string|max:50|unique:staff_positions,code' . ($ignoreId ? ",{$ignoreId}" : ''),
            'name' => 'required|string|max:255',
        ]);
    }

    private function respondWithSuccess(string $message)
    {
        return response('')
            ->header('HX-Trigger', json_encode([
                'close-modal' => true,
                'refreshPositions' => true, // Trigger untuk memuat ulang tab Jabatan
                'showAlert' => [
                    'icon'  => 'success',
                    'title' => 'Berhasil!',
                    'text'  => $message,
                ],
            ]));
    }
}
