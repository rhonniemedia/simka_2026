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
        $this->normalizeFilters($request);

        $search   = $request->input('search');
        $viewMode = $request->input('view') === 'staff' ? 'staff' : 'all';

        $documents = null;
        $staffList = null;

        if ($viewMode === 'staff') {
            $table      = (new Data)->getTable();
            $hasFilters = $this->activeFilterCount($request) > 0;
            $missing    = $request->input('filter_presence') === 'missing';

            // Dua mode, memakai filter yang sama:
            //  has     (bawaan): pegawai yang PUNYA >= 1 dokumen sesuai filter; documents_count = jumlah yang cocok.
            //  missing         : pegawai yang TIDAK punya satu pun dokumen sesuai filter
            //                    (tanpa filter lain = pegawai tanpa dokumen sama sekali);
            //                    total_documents_count = seluruh berkas pegawai itu, sebagai konteks.
            $staffList = Data::query()
                ->select("{$table}.*")
                ->with(['vault'])
                ->addSelect([
                    'documents_count' => $this->applyFilters(
                        Document::query()->selectRaw('count(*)')->whereColumn('staff_id', "{$table}.id"),
                        $request
                    ),
                ])
                ->when($missing, fn($q) => $q->addSelect([
                    'total_documents_count' => Document::query()->selectRaw('count(*)')->whereColumn('staff_id', "{$table}.id"),
                ]))
                ->when($search, fn($q, $s) => $q->where('name', 'like', "%{$s}%"))
                ->when($missing, fn($q) => $q->whereNotIn(
                    'id',
                    // whereNotNull: NOT IN dengan nilai NULL membuat seluruh hasil kosong
                    $this->applyFilters(Document::query(), $request)->whereNotNull('staff_id')->select('staff_id')
                ))
                ->when(! $missing && $hasFilters, fn($q) => $q->whereIn(
                    'id',
                    $this->applyFilters(Document::query(), $request)->select('staff_id')
                ))
                ->orderBy('name')
                ->paginate(10)
                ->withQueryString();
        } else {
            $documents = $this->applyFilters(
                Document::query()
                    ->with(['staff', 'category', 'creator', 'linkedRecord'])
                    ->when($search, fn($query, $s) => $query->whereHas('staff', fn($q) => $q->where('name', 'like', "%{$s}%"))),
                $request
            )
                ->orderByDesc('created_at')
                ->paginate(10)
                ->withQueryString();
        }

        return view('pages.admin.administration.documents.index', array_merge(
            $this->filterViewData($request, $viewMode),
            [
                'viewMode'  => $viewMode,
                'documents' => $documents,
                'staffList' => $staffList,
                'search'    => $search,
            ]
        ));
    }

    // Halaman detail dokumen milik satu pegawai (route: admin.personnel.documents.staff)
    public function show(Request $request, $staffId)
    {
        $this->normalizeFilters($request);

        $search = $request->input('search');
        $staff  = Data::with(['vault'])->findOrFail($staffId);

        $documents = $this->applyFilters(
            Document::query()
                ->with(['category', 'creator', 'linkedRecord'])
                ->where('staff_id', $staffId)
                // Di halaman ini pencarian berlaku pada dokumen (nama / nomor), bukan nama pegawai.
                ->when($search, fn($query, $s) => $query->where(
                    fn($w) => $w->where('document_name', 'like', "%{$s}%")
                        ->orWhere('document_number', 'like', "%{$s}%")
                )),
            $request
        )
            ->orderByDesc('document_date')
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        $backParam = $this->resolveBackParam($request);

        return view('pages.admin.administration.documents.partials._show', array_merge(
            $this->filterViewData($request),
            [
                'staff'         => $staff,
                'documents'     => $documents,
                'search'        => $search,
                // Tujuan tombol "Kembali": daftar asal (lengkap dengan pencarian, filter, halaman).
                'backUrl'       => $backParam ?? route('admin.personnel.documents.index', ['view' => 'staff']),
                // Parameter yang harus ikut di setiap request filter halaman ini.
                'persistParams' => $backParam ? ['back' => $backParam] : [],
            ]
        ));
    }

    /*
    |--------------------------------------------------------------------
    | Filter (dipakai tab Semua Dokumen, tab Per Pegawai, dan halaman detail)
    |--------------------------------------------------------------------
    */

    private function applyFilters($query, Request $request)
    {
        return $query
            ->categoryId($request->input('filter_category'))
            ->verificationStatus($request->input('filter_verification'))
            ->linkStatus($request->input('filter_link'))
            ->when($request->input('filter_date_from'), fn($q, $v) => $q->whereDate('document_date', '>=', $v))
            ->when($request->input('filter_date_to'), fn($q, $v) => $q->whereDate('document_date', '<=', $v));
    }

    /**
     * Membersihkan input filter SEBELUM dipakai: nilai tidak valid dibuang
     * (jadi dianggap tidak ada filter) dan rentang tanggal terbalik ditukar.
     * Dengan begitu query, hitungan filter aktif, dan tampilan selalu konsisten.
     */
    private function normalizeFilters(Request $request): void
    {
        $dateOk = fn($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) === 1;

        $from = $request->input('filter_date_from');
        $to   = $request->input('filter_date_to');
        $from = $dateOk($from) ? $from : null;
        $to   = $dateOk($to) ? $to : null;

        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        $category     = $request->input('filter_category');
        $verification = $request->input('filter_verification');
        $link         = $request->input('filter_link');

        $request->merge([
            'filter_category'     => is_string($category) && Str::isUuid($category) ? $category : null,
            'filter_verification' => in_array($verification, ['draft', 'verified', 'rejected'], true) ? $verification : null,
            'filter_link'         => in_array($link, ['linked', 'unlinked', 'standalone'], true) ? $link : null,
            // 'has' = perilaku bawaan, jadi tidak perlu disimpan sebagai filter
            'filter_presence'     => $request->input('filter_presence') === 'missing' ? 'missing' : null,
            'filter_date_from'    => $from,
            'filter_date_to'      => $to,
        ]);
    }

    // Jumlah kelompok filter yang aktif (rentang tanggal dihitung satu).
    // filter_presence hanya relevan di tab Per Pegawai, jadi baru dihitung bila diminta.
    private function activeFilterCount(Request $request, bool $includePresence = false): int
    {
        $count = 0;

        if ($includePresence && $request->input('filter_presence') === 'missing') {
            $count++;
        }

        foreach (['filter_category', 'filter_verification', 'filter_link'] as $key) {
            if (filled($request->input($key))) {
                $count++;
            }
        }

        if (filled($request->input('filter_date_from')) || filled($request->input('filter_date_to'))) {
            $count++;
        }

        return $count;
    }

    // Data filter yang dikirim ke view (modal, indikator, chip filter aktif).
    private function filterViewData(Request $request, string $viewMode = 'all'): array
    {
        return [
            'filterPresence'     => $request->input('filter_presence'),
            'filterCategory'     => $request->input('filter_category'),
            'filterVerification' => $request->input('filter_verification'),
            'filterLink'         => $request->input('filter_link'),
            'filterDateFrom'     => $request->input('filter_date_from'),
            'filterDateTo'       => $request->input('filter_date_to'),
            'activeFilterCount'  => $this->activeFilterCount($request, $viewMode === 'staff'),
            'categoryOptions'    => DocumentCategory::orderBy('name')->pluck('name', 'id'),
        ];
    }

    /**
     * Parameter "back" (URL daftar asal) hanya diterima bila mengarah ke halaman
     * index dokumen. Yang dipakai hanya path + query-nya, jadi tidak bisa dipakai
     * untuk mengarahkan pengguna ke situs lain (open redirect).
     */
    private function resolveBackParam(Request $request): ?string
    {
        $back = $request->input('back');

        if (! is_string($back) || $back === '') {
            return null;
        }

        $indexPath = parse_url(route('admin.personnel.documents.index'), PHP_URL_PATH);
        $parts     = parse_url($back);

        if ($parts === false || ($parts['path'] ?? null) !== $indexPath) {
            return null;
        }

        return $indexPath . (isset($parts['query']) ? '?' . $parts['query'] : '');
    }

    public function create(Request $request)
    {
        // Dari halaman detail pegawai, tombol Tambah mengirim ?staff_id=... supaya pegawai terpilih otomatis.
        $staffId = $request->input('staff_id');

        return view('pages.admin.administration.documents.partials._document-form', array_merge(
            $this->formOptions(),
            ['selectedStaffId' => is_string($staffId) && Str::isUuid($staffId) ? $staffId : null]
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

    public function edit($id)
    {
        $item = Document::with('category')->findOrFail($id);

        // Cek apakah dokumen ini bertaut ke data riwayat tertentu
        $isLinked = filled($item->linked_record_id) || filled($item->category?->linked_table);

        return view('pages.admin.administration.documents.partials._document-form', array_merge(
            $this->formOptions(),
            [
                'item'     => $item,
                'isLinked' => $isLinked, // Kirim status tautan ke view
            ]
        ));
    }

    public function update(Request $request, $id)
    {
        $item = Document::with('category')->findOrFail($id);
        $isLinked = filled($item->linked_record_id) || filled($item->category?->linked_table);

        // JIKA DOKUMEN BERTAUT: Hanya izinkan mengubah verification_status
        if ($isLinked) {
            $validated = $request->validate([
                'verification_status' => 'required|in:draft,verified,rejected',
            ]);

            $item->update([
                'verification_status' => $validated['verification_status'],
                'updated_by'          => Auth::id(),
            ]);

            return $this->respondWithSuccess('Status verifikasi dokumen berhasil diperbarui.');
        }

        // JIKA DOKUMEN MANDIRI: Jalankan proses update penuh seperti biasa
        $validated = $this->validated($request, isCreate: false);

        $payload = array_merge($validated, ['updated_by' => Auth::id()]);
        unset($payload['file']);

        if ($request->hasFile('file')) {
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

            // Mengambil kategori dokumen mandiri dan mengurutkannya berdasarkan sort_order
            'categoryOptions' => DocumentCategory::where(function ($query) {
                $query->where('linked_table', '')
                    ->orWhereNull('linked_table');
            })
                ->orderBy('sort_order') // Diurutkan berdasarkan sort_order
                ->orderBy('name')       // Urutan cadangan jika sort_order bernilai sama
                ->get(['id', 'name'])
                ->map(fn($c) => ['value' => $c->id, 'label' => $c->name])
                ->toArray(),
        ];
    }

    private function validated(Request $request, bool $isCreate): array
    {
        return $request->validate([
            'staff_id'                   => 'required|uuid|exists:staff_data,id',
            // Pastikan kategori yang dipilih valid DAN tidak memiliki linked_table (harus mandiri)
            'staff_document_category_id' => [
                'required',
                'uuid',
                'exists:staff_document_categories,id',
                function ($attribute, $value, $fail) {
                    $category = DocumentCategory::find($value);
                    if ($category && filled($category->linked_table)) {
                        $fail('Kategori dokumen ini terikat pada data riwayat khusus dan harus diunggah melalui menu terkait.');
                    }
                },
            ],
            'document_name'              => 'required|string|max:255',
            'document_number'            => 'nullable|string|max:255',
            'document_date'              => 'nullable|date',
            'verification_status'        => 'required|in:draft,verified,rejected',
            'file'                       => ($isCreate ? 'required' : 'nullable') . '|file|mimes:pdf|max:1024',
        ]);
    }

    // Nama file fisik SENGAJA diganti UUID (bukan nama asli) supaya tidak
    // bisa ditebak/dienumerasi dan menghindari konflik nama antar-upload.
    // Dipartisi per staff_id supaya rapi & mudah dibersihkan per-pegawai.
    private function storeFile(UploadedFile $file, string $staffId): string
    {
        $uuid = (string) Str::uuid();
        // Hanya PDF yang lolos validasi, jadi ekstensi dikunci 'pdf' (bukan dari nama file kiriman pengguna).
        $storedPath = "{$staffId}/{$uuid}.pdf";

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
