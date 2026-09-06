<?php

namespace App\Http\Controllers\Admin\Personnel;

use App\Http\Controllers\Controller;
use App\Models\Data;
use App\Models\Document;
use App\Models\DocumentCategory;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $search             = $request->input('search');
        $filterCategory     = $request->input('filter_category');
        $filterVerification = $request->input('filter_verification');
        $filterDateFrom     = $request->input('filter_date_from');
        $filterDateTo       = $request->input('filter_date_to');

        $documents = Document::query()
            ->with(['staff', 'category'])
            ->when($search, function ($query, $search) {
                $query->whereHas('staff', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
            ->categoryId($filterCategory)
            ->verificationStatus($filterVerification)
            ->when($filterDateFrom, fn($query, $value) => $query->whereDate('document_date', '>=', $value))
            ->when($filterDateTo, fn($query, $value) => $query->whereDate('document_date', '<=', $value))
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        $categoryOptions = DocumentCategory::orderBy('name')->pluck('name', 'id');

        return view('pages.admin.personnel.document.index', [
            'documents'           => $documents,
            'search'              => $search,
            'filterCategory'      => $filterCategory,
            'filterVerification'  => $filterVerification,
            'filterDateFrom'      => $filterDateFrom,
            'filterDateTo'        => $filterDateTo,
            'categoryOptions'     => $categoryOptions,
        ]);
    }

    public function create()
    {
        return view('pages.admin.personnel.document.partials._document-form', $this->formOptions());
    }

    public function edit($id)
    {
        $item = Document::findOrFail($id);

        return view('pages.admin.personnel.document.partials._document-form', array_merge(
            $this->formOptions(),
            ['item' => $item]
        ));
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request, isCreate: true);

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $storedPath = $this->storeFile($file, $validated['staff_id']);

        unset($validated['file']);

        Document::create(array_merge($validated, [
            'file_path'         => $storedPath,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type'         => $file->getClientMimeType(),
            'file_size'         => $file->getSize(),
            'created_by'        => auth()->id(),
            'updated_by'        => auth()->id(),
        ]));

        return $this->respondWithSuccess('Dokumen berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $item = Document::findOrFail($id);
        $validated = $this->validated($request, isCreate: false);

        $payload = array_merge($validated, ['updated_by' => auth()->id()]);
        unset($payload['file']);

        // File baru diupload -> ganti file lama. Kalau tidak, biarkan
        // file_path/metadata lama tetap seperti sebelumnya.
        if ($request->hasFile('file')) {
            Storage::disk('documents')->delete($item->file_path);

            /** @var UploadedFile $file */
            $file = $request->file('file');
            $payload['file_path']         = $this->storeFile($file, $validated['staff_id']);
            $payload['original_filename'] = $file->getClientOriginalName();
            $payload['mime_type']         = $file->getClientMimeType();
            $payload['file_size']         = $file->getSize();
        }

        $item->update($payload);

        return $this->respondWithSuccess('Dokumen berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $item = Document::findOrFail($id);

        // Soft delete saja - file fisik SENGAJA tidak ikut dihapus di sini,
        // supaya dokumen masih bisa dipulihkan kalau soft-delete keliru.
        // Hapus file fisik baru dilakukan nanti saat ada fitur hapus permanen
        // (forceDelete), bukan di sini.
        $item->delete();

        return $this->respondWithSuccess('Dokumen berhasil dihapus.');
    }

    // Menampilkan file inline di tab baru (mis. PDF langsung tampil di browser)
    public function preview($id)
    {
        $item = Document::findOrFail($id);

        return Storage::disk('documents')->response(
            $item->file_path,
            $item->original_filename
        );
    }

    // Memaksa file terunduh ke perangkat, nama file sesuai nama asli saat diupload
    public function download($id)
    {
        $item = Document::findOrFail($id);

        return Storage::disk('documents')->download(
            $item->file_path,
            $item->original_filename
        );
    }

    /*
    |--------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------
    */

    private function formOptions(): array
    {
        return [
            'staffOptions' => Data::orderBy('name')->get(['id', 'name'])
                ->map(fn($s) => ['value' => $s->id, 'label' => $s->name])
                ->toArray(),
            'categoryOptions' => DocumentCategory::orderBy('name')->get(['id', 'name'])
                ->map(fn($c) => ['value' => $c->id, 'label' => $c->name])
                ->toArray(),
        ];
    }

    private function validated(Request $request, bool $isCreate): array
    {
        return $request->validate([
            'staff_id'                    => 'required|uuid|exists:staff_data,id',
            'staff_document_category_id'  => 'required|uuid|exists:staff_document_categories,id',
            'document_name'                => 'required|string|max:255',
            'document_number'              => 'nullable|string|max:255',
            'document_date'                => 'nullable|date',
            'verification_status'          => 'required|in:draft,verified,rejected',
            'file'                         => ($isCreate ? 'required' : 'nullable') . '|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);
    }

    // Nama file fisik SENGAJA diganti UUID (bukan nama asli) supaya tidak
    // bisa ditebak/dienumerasi dan menghindari konflik nama antar-upload.
    // Dipartisi per staff_id supaya rapi & mudah dibersihkan per-pegawai.
    private function storeFile(UploadedFile $file, string $staffId): string
    {
        $uuid = (string) Str::uuid();
        $extension = $file->getClientOriginalExtension();
        $storedPath = "{$staffId}/{$uuid}.{$extension}";

        Storage::disk('documents')->putFileAs(
            dirname($storedPath),
            $file,
            basename($storedPath)
        );

        return $storedPath;
    }

    private function respondWithSuccess(string $message)
    {
        return response('')
            ->header('HX-Trigger', json_encode([
                'close-modal'       => true,
                'refreshDocuments'  => true,
                'showAlert' => [
                    'icon'  => 'success',
                    'title' => 'Berhasil!',
                    'text'  => $message,
                ],
            ]));
    }
}
