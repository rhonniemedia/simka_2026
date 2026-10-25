<?php

namespace App\Http\Controllers\Admin\Master\Reference;

use App\Enums\Staff\DocumentGroup;
use App\Http\Controllers\Controller;
use App\Models\DocumentCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmployeeDocumentController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->header('HX-Request')) {
            // Kirim identitas tab langsung dari Controller
            return view('pages.admin.master.references.index', [
                'activeTab' => 'document_categories',
                'fetchUrl'  => $request->fullUrl()
            ]);
        }

        // Diurutkan berdasarkan group, kemudian data terbaru
        $documentCategories = DocumentCategory::orderBy('group', 'asc')
            ->latest()
            ->paginate(10);

        return view('pages.admin.master.references.document.document-categories', compact('documentCategories'));
    }

    public function create()
    {
        $item = null;
        return view('pages.admin.master.references.document.partials._document-category-form', compact('item'));
    }

    public function store(Request $request)
    {
        try {
            $validated = $this->validated($request);
        } catch (ValidationException $e) {
            return $this->respondWithError($e->errors());
        }

        DocumentCategory::create($validated);

        return $this->respondWithSuccess('Kategori Dokumen berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $item = DocumentCategory::findOrFail($id);
        return view('pages.admin.master.references.document.partials._document-category-form', compact('item'));
    }

    public function update(Request $request, $id)
    {
        $item = DocumentCategory::findOrFail($id);

        try {
            $validated = $this->validated($request, $id);
        } catch (ValidationException $e) {
            return $this->respondWithError($e->errors());
        }

        $item->update($validated);

        return $this->respondWithSuccess('Kategori Dokumen berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $item = DocumentCategory::findOrFail($id);
        $item->delete();

        return $this->respondWithSuccess('Kategori Dokumen berhasil dihapus.');
    }

    /*
    |--------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------
    */

    /**
     * Validasi input. $id diisi saat update, agar validasi unique
     * mengabaikan record (UUID) yang sedang diedit.
     */
    private function validated(Request $request, $id = null): array
    {
        $nameRule = Rule::unique(DocumentCategory::class, 'name');

        if ($id) {
            $nameRule = $nameRule->ignore($id);
        }

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255', $nameRule],
            'code'  => ['nullable', 'string', 'max:50', 'alpha_dash', Rule::unique(DocumentCategory::class, 'code')->ignore($id)],
            'group' => ['required', Rule::enum(DocumentGroup::class)],
            'linked_table' => ['nullable', Rule::in(array_keys(DocumentCategory::LINKABLE_TABLES))],
            'requires_document_number' => ['sometimes', 'boolean'],
            'is_active'                => ['sometimes', 'boolean'],
        ], [
            'name.unique' => 'Nama kategori dokumen ini sudah terdaftar, gunakan nama lain.',
            'code.unique' => 'Kode kategori ini sudah dipakai.',
        ]);

        // Checkbox yang tidak dicentang tidak ikut dikirim browser, jadi
        // ambil nilai boolean-nya langsung dari request (bukan dari hasil
        // $request->validate(), yang akan kosong kalau checkbox tidak dicentang).
        $validated['requires_document_number'] = $request->boolean('requires_document_number');
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    private function respondWithSuccess(string $message)
    {
        return response('')
            ->header('HX-Trigger', json_encode([
                'close-modal' => true,
                'refreshDocumentCategories' => true, // Trigger untuk memuat ulang tab
                'showAlert' => [
                    'icon'  => 'success',
                    'title' => 'Berhasil!',
                    'text'  => $message,
                ],
            ]));
    }

    /**
     * Response saat validasi gagal. Modal TIDAK ditutup (tidak ada
     * 'close-modal') supaya user bisa memperbaiki inputnya.
     */
    private function respondWithError(array $errors)
    {
        $message = collect($errors)->flatten()->first()
            ?? 'Terjadi kesalahan, silakan periksa kembali data Anda.';

        return response('', 422)
            ->header('HX-Trigger', json_encode([
                'showAlert' => [
                    'icon'  => 'error',
                    'title' => 'Gagal!',
                    'text'  => $message,
                ],
            ]));
    }
}
