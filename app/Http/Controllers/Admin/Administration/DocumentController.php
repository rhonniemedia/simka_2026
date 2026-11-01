<?php

namespace App\Http\Controllers\Admin\Administration;

use App\Http\Controllers\Controller;
use App\Models\Data;
use App\Models\Document;
use App\Models\DocumentCategory;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $search             = $request->input('search');
        $filterCategory     = $request->input('filter_category');
        $filterVerification = $request->input('filter_verification');
        $filterDateFrom     = $request->input('filter_date_from');
        $filterDateTo       = $request->input('filter_date_to');
        $viewMode           = $request->input('view') === 'staff' ? 'staff' : 'all';

        $documents = null;
        $staffList = null;

        if ($viewMode === 'staff') {
            $table = (new Data)->getTable();
            $hasFilters = filled($filterCategory) || filled($filterVerification)
                || filled($filterDateFrom) || filled($filterDateTo);

            $staffList = Data::query()
                ->select("{$table}.*")
                ->addSelect([
                    'documents_count' => $this->applyFilters(
                        Document::query()->selectRaw('count(*)')->whereColumn('staff_id', "{$table}.id"),
                        $request
                    ),
                ])
                ->when($search, fn($q, $s) => $q->where('name', 'like', "%{$s}%"))
                // Kalau ada filter aktif, tampilkan hanya pegawai yang punya dokumen sesuai filter
                ->when($hasFilters, fn($q) => $q->whereIn(
                    'id',
                    $this->applyFilters(Document::query(), $request)->select('staff_id')
                ))
                ->orderBy('name')
                ->paginate(10)
                ->withQueryString();
        } else {
            $documents = $this->applyFilters(
                Document::query()
                    ->with(['staff', 'category'])
                    ->when($search, fn($query, $s) => $query->whereHas('staff', fn($q) => $q->where('name', 'like', "%{$s}%"))),
                $request
            )
                ->orderByDesc('created_at')
                ->paginate(10)
                ->withQueryString();
        }

        $categoryOptions = DocumentCategory::orderBy('name')->pluck('name', 'id');

        return view('pages.admin.administration.documents.index', [
            'viewMode'           => $viewMode,
            'documents'          => $documents,
            'staffList'          => $staffList,
            'search'             => $search,
            'filterCategory'     => $filterCategory,
            'filterVerification' => $filterVerification,
            'filterDateFrom'     => $filterDateFrom,
            'filterDateTo'       => $filterDateTo,
            'categoryOptions'    => $categoryOptions,
        ]);
    }

    // Dokumen milik satu pegawai (dimuat saat accordion dibuka)
    public function staffDocuments(Request $request, $staff)
    {
        $documents = $this->applyFilters(
            Document::query()->with('category')->where('staff_id', $staff),
            $request
        )
            ->orderByDesc('document_date')
            ->orderByDesc('created_at')
            ->get();

        return view('pages.admin.administration.documents.partials._staff-documents', [
            'documents' => $documents,
        ]);
    }

    private function applyFilters($query, Request $request)
    {
        return $query
            ->categoryId($request->input('filter_category'))
            ->verificationStatus($request->input('filter_verification'))
            ->when($request->input('filter_date_from'), fn($q, $v) => $q->whereDate('document_date', '>=', $v))
            ->when($request->input('filter_date_to'), fn($q, $v) => $q->whereDate('document_date', '<=', $v));
    }

    public function create()
    {
        return view('pages.admin.administration.documents.partials._document-form', $this->formOptions());
    }

    public function edit($id)
    {
        $item = Document::findOrFail($id);
        return view('pages.admin.administration.documents.partials._document-form', array_merge(
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
            'created_by'        => Auth::id(),
            'updated_by'        => Auth::id(),
        ]));

        return $this->respondWithSuccess('Dokumen berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $item = Document::findOrFail($id);
        $validated = $this->validated($request, isCreate: false);

        $payload = array_merge($validated, ['updated_by' => Auth::id()]);
        unset($payload['file']);

        // File baru diupload -> ganti file lama. Kalau tidak, biarkan
        // file_path/metadata lama tetap seperti sebelumnya.
        if ($request->hasFile('file')) {
            // Berkas lama bisa saja sudah hilang dari penyimpanan; delete()
            // pada disk 'throw' => true tetap aman karena hanya mengembalikan
            // false/true untuk file yang tidak ada, tapi dibungkus agar update
            // tidak gagal hanya karena berkas lama bermasalah.
            try {
                if (filled($item->file_path)) {
                    Storage::disk('documents')->delete($item->file_path);
                }
            } catch (Throwable $e) {
                report($e);
            }

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
        $item = Document::with('staff')->findOrFail($id);

        if (! $this->fileExists($item)) {
            return $this->fileNotFoundResponse($item);
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('documents');

        return $disk->response(
            $item->file_path,
            $item->original_filename
        );
    }

    // Memaksa file terunduh ke perangkat, nama file sesuai nama asli saat diupload
    public function download($id)
    {
        $item = Document::with('staff')->findOrFail($id);

        if (! $this->fileExists($item)) {
            return $this->fileNotFoundResponse($item);
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('documents');

        return $disk->download(
            $item->file_path,
            $item->original_filename
        );
    }

    /*
    |--------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------
    */

    /**
     * Cek berkas fisik ada di disk 'documents'. Dibungkus try/catch karena
     * disk ini memakai 'throw' => true, sehingga masalah I/O akan melempar
     * exception; di sini dianggap sebagai "berkas tidak tersedia".
     */
    private function fileExists(Document $item): bool
    {
        if (blank($item->file_path)) {
            return false;
        }

        try {
            return Storage::disk('documents')->exists($item->file_path);
        } catch (Throwable $e) {
            report($e);
            return false;
        }
    }

    /** Halaman ramah pengguna saat berkas fisik tidak ditemukan (HTTP 404). */
    private function fileNotFoundResponse(Document $item)
    {
        return response()->view(
            'pages.admin.administration.documents.not-found',
            ['item' => $item],
            404
        );
    }

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

    // private function formOptions(): array
    // {
    //     return [
    //         'staffOptions' => Data::orderBy('name')->get(['id', 'name'])
    //             ->map(fn($s) => ['value' => $s->id, 'label' => $s->name])
    //             ->toArray(),
    //         'categoryOptions' => DocumentCategory::where(function ($query) {
    //             $query->where('linked_table', '')
    //                 ->orWhereNull('linked_table');
    //         })
    //             ->orderBy('name')
    //             ->get(['id', 'name'])
    //             ->map(fn($c) => ['value' => $c->id, 'label' => $c->name])
    //             ->toArray(),
    //     ];
    // }

    private function validated(Request $request, bool $isCreate): array
    {
        return $request->validate([
            'staff_id'                    => 'required|uuid|exists:staff_data,id',
            'staff_document_category_id'  => 'required|uuid|exists:staff_document_categories,id',
            'document_name'               => 'required|string|max:255',
            'document_number'             => 'nullable|string|max:255',
            'document_date'               => 'nullable|date',
            'verification_status'         => 'required|in:draft,verified,rejected',
            'file'                        => ($isCreate ? 'required' : 'nullable') . '|file|mimes:pdf,jpg,jpeg,png|max:10240',
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

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('documents');

        $disk->putFileAs(
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
