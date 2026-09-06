<?php

namespace App\Http\Controllers\Admin\Documents;

use App\Http\Controllers\Controller;
use App\Models\DocumentCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DocumentCategoryController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->header('HX-Request')) {
            // Kirim identitas tab langsung dari Controller
            return view('pages.admin.master.index', [
                'activeTab' => 'document_categories',
                'fetchUrl'  => $request->fullUrl()
            ]);
        }

        // Diurutkan berdasarkan group, kemudian data terbaru
        $documentCategories = DocumentCategory::orderBy('group', 'asc')
            ->latest()
            ->paginate(10);

        return view('pages.admin.master.partials.document-categories', compact('documentCategories'));
    }

    public function create()
    {
        $item = null;
        return view('pages.admin.master.partials._document-category-form', compact('item'));
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
        return view('pages.admin.master.partials._document-category-form', compact('item'));
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
            'name'                     => ['required', 'string', 'max:255', $nameRule],
            'group'                    => ['required', Rule::in([
                'akta',
                'ijazah',
                'kartu_identitas',
                'pak',
                'sertifikat',
                'sk_berkala',
                'sk_fungsional',
                'sk_pangkat',
                'sk_pengangkatan',
                'skp',
                'spmt',
                'lainnya',
            ])],
            'linked_table'             => ['nullable', 'string', 'max:255'],
            'requires_document_number' => ['sometimes', 'boolean'],
            'is_active'                => ['sometimes', 'boolean'],
        ], [
            'name.unique' => 'Nama kategori dokumen ini sudah terdaftar, gunakan nama lain.',
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
