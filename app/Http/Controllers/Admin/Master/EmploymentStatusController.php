<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Models\EmploymentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EmploymentStatusController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->header('HX-Request')) {
            // Kirim identitas tab langsung dari Controller
            return view('pages.admin.master.index', [
                'activeTab' => 'employment_statuses',
                'fetchUrl'  => $request->fullUrl()
            ]);
        }

        $employmentStatuses = EmploymentStatus::orderBy('code')->get();
        return view('pages.admin.master.partials.employment-statuses', compact('employmentStatuses'));
    }

    public function create()
    {
        $item = null;
        return view('pages.admin.master.partials._employment-status-form', compact('item'));
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $validated['slug'] = $this->generateUniqueSlug($validated['name']);

        EmploymentStatus::create($validated);

        return $this->respondWithSuccess('Status Kepegawaian berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $item = EmploymentStatus::findOrFail($id);
        return view('pages.admin.master.partials._employment-status-form', compact('item'));
    }

    public function update(Request $request, $id)
    {
        $item = EmploymentStatus::findOrFail($id);
        $validated = $this->validated($request, $id);

        $item->update($validated);

        return $this->respondWithSuccess('Status Kepegawaian berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $item = EmploymentStatus::findOrFail($id);
        $item->delete();

        return $this->respondWithSuccess('Status Kepegawaian berhasil dihapus.');
    }

    /*
    |--------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------
    */

    private function validated(Request $request, ?string $ignoreId = null): array
    {
        return $request->validate([
            'code'  => 'required|string|max:50|unique:staff_employment_statuses,code' . ($ignoreId ? ",{$ignoreId}" : ''),
            'name'  => 'required|string|max:255',
            'alias' => 'nullable|string|max:255',
        ]);
    }

    private function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base !== '' ? $base : 'status-kepegawaian';
        $i = 1;

        while (EmploymentStatus::where('slug', $slug)->exists()) {
            $i++;
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    private function respondWithSuccess(string $message)
    {
        return response('')
            ->header('HX-Trigger', json_encode([
                'close-modal' => true,
                'refreshEmploymentStatuses' => true, // Memicu refresh tabel status
                'showAlert' => [
                    'icon'  => 'success',
                    'title' => 'Berhasil!',
                    'text'  => $message,
                ],
            ]));
    }
}
