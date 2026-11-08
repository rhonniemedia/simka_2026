<?php

namespace App\Http\Controllers\Admin\Administration;

use App\Http\Controllers\Controller;
use App\Models\Data;
use App\Models\EducationHistory;
use App\Models\EducationLevel;
use App\Models\EmploymentStatus;
use App\Models\PersonnelType;
use App\Models\Position;
use App\Services\Staff\StaffDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EducationController extends Controller
{
    /**
     * Field berkas pada form => kode kategori dokumen (kolom `code` di
     * staff_document_categories).
     */
    private const DOCUMENT_FIELDS = [
        'file_ijazah'     => 'ijazah',
        'file_transcript' => 'transkrip_nilai',
    ];

    /** Field berkas pada form => field status dokumen untuk berkas itu. */
    private const STATUS_FIELDS = [
        'file_ijazah'     => 'status_ijazah',
        'file_transcript' => 'status_transcript',
    ];

    /**
     * Field form nomor & tanggal Transkrip Nilai. Disimpan LANGSUNG di baris
     * dokumen (document_number & document_date), bukan di riwayat pendidikan,
     * karena itu tidak ikut dimasukkan ke data riwayat (lihat educationData()).
     * Nomor & tanggal Ijazah tetap memakai certificate_number/date di riwayat.
     */
    private const TRANSCRIPT_FIELDS = [
        'number' => 'transcript_number',
        'date'   => 'transcript_date',
    ];

    public function index(Request $request)
    {
        // 1. Ambil parameter dari request
        $filterEmploymentStatus = $request->input('filter_employment_status');
        $filterPersonnel = $request->input('filter_personnel');
        $filterPosition = $request->input('filter_position');
        $filterGender = $request->input('filter_gender');
        $search = $request->input('search');

        // 2. Inisiasi Query beserta relasinya (hanya pegawai lengkap & active)
        $query = Data::completed()->active()->with([
            'vault',
            'personnelType',
            'grade',
            'employmentStatus',
            'highestEducation.level'
        ]);

        // 3. Logika Pencarian (Nama & Hash NIK/NIP/NUPTK)
        if (!empty($search)) {
            $searchHash = hash('sha256', trim($search));

            $query->where(function ($q) use ($search, $searchHash) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('vault', function ($qVault) use ($searchHash) {
                        $qVault->where('nik_hash', $searchHash)
                            ->orWhere('nip_hash', $searchHash)
                            ->orWhere('nuptk_hash', $searchHash);
                    });
            });
        }

        // 4. Logika Filter
        if (!empty($filterEmploymentStatus)) {
            $query->where('employment_id', $filterEmploymentStatus);
        }

        if (!empty($filterPersonnel)) {
            $query->where('personnel_id', $filterPersonnel);
        }

        if (!empty($filterPosition)) {
            $query->where('position_id', $filterPosition);
        }

        if (!empty($filterGender)) {
            $query->where('gender', $filterGender);
        }

        // 5. Eksekusi Paginasi (Berdasarkan abjad)
        $staff = $query->orderBy('name', 'asc')->paginate(10)->withQueryString();

        // 6. Siapkan Data Opsi untuk Select Filter menggunakan Model
        $employmentOptions = EmploymentStatus::pluck('name', 'id') ?? collect();
        $personnelOptions = PersonnelType::pluck('name', 'id') ?? collect();
        $positionOptions = Position::pluck('name', 'id') ?? collect();

        // 6b. Statistik kartu pendidikan
        $stats = $this->getStats();

        // 7. Render view parsial jika request datang dari HTMX
        if ($request->header('HX-Request')) {
            return view('pages.admin.administration.education.partials._table', compact('staff'));
        }

        // 8. Render halaman utama penuh
        return view('pages.admin.administration.education.index', array_merge(
            compact(
                'staff',
                'search',
                'filterEmploymentStatus',
                'filterPersonnel',
                'filterPosition',
                'filterGender',
                'employmentOptions',
                'personnelOptions',
                'positionOptions'
            ),
            $stats
        ));
    }

    private function getStats(): array
    {
        // Hanya pegawai lengkap & active. Distinct agar pegawai dengan 2 riwayat S1 tetap dihitung 1.
        $educationStats = Data::completed()
            ->active()
            ->join('staff_education_histories', 'staff_data.id', '=', 'staff_education_histories.staff_id')
            ->join('staff_education_levels', 'staff_education_histories.education_level_id', '=', 'staff_education_levels.id')
            ->where('staff_education_histories.verification_status', 'verified')
            ->selectRaw('staff_education_levels.alias, count(distinct staff_data.id) as total')
            ->groupBy('staff_education_levels.id', 'staff_education_levels.alias')
            ->pluck('total', 'alias');

        return [
            'totalVerified'  => $educationStats->sum(),
            'pascaStats'     => $educationStats->get('s2', 0) + $educationStats->get('s3', 0),
            'sarjanaStats'   => $educationStats->get('s1', 0) + $educationStats->get('d4', 0),
            'menengahStats'  => $educationStats->get('sma', 0) + $educationStats->get('smk', 0) + $educationStats->get('d3', 0),
        ];
    }

    public function destroy(Request $request, $id)
    {
        $staff = Data::findOrFail($id);

        // Asumsi relasi di model Data memiliki cascade delete atau ditangani event
        $staff->delete();

        if ($request->header('HX-Request')) {
            $table = $this->index($request)->render();
            $statsOob = view('pages.admin.administration.education.partials._stats-cards', array_merge(
                $this->getStats(),
                ['isOob' => true]
            ))->render();

            return response($table . $statsOob)
                ->header('HX-Trigger', json_encode([
                    'showAlert' => [
                        'icon'  => 'success',
                        'title' => 'Dihapus!',
                        'text'  => 'Data pegawai berhasil dihapus.',
                    ],
                ]));
        }

        return $this->index($request);
    }

    public function detailPersonal($id)
    {
        // Mengubah cakupan detail untuk memuat riwayat pendidikan
        $staff = Data::completed()->active()->with(['vault', 'educationHistories.level'])->findOrFail($id);
        return view('pages.admin.administration.education.modals._detail-personal', compact('staff'));
    }

    public function detailEmployment($id)
    {
        $staff = Data::completed()->active()->with(['personnelType', 'position'])->findOrFail($id);
        return view('pages.admin.administration.education.modals._detail-employment', compact('staff'));
    }

    public function editPersonal($id)
    {
        $staff = Data::completed()->active()->with(['vault', 'educationHistories'])->findOrFail($id);
        return view('pages.admin.administration.education.modals._edit-personal', compact('staff'));
    }

    public function show(Request $request, $id)
    {
        // Ambil data pegawai beserta relasi dasar
        $staff = Data::with(['vault', 'employmentStatus'])->findOrFail($id);

        // Paginasi riwayat pendidikan (bukan eager loading collection lagi)
        $educations = $staff->educations()
            ->with(['level', 'documents.category'])
            ->orderBy('graduation_date', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Jika request datang dari HTMX (refresh tabel, pagination, atau
        // dipanggil ulang setelah store/update), cukup kembalikan partial
        // tabelnya saja -- JANGAN kembalikan halaman penuh, karena akan
        // di-swap ke dalam <div id="education-container"> di client.
        if ($request->header('HX-Request')) {
            return view('pages.admin.administration.education.show.partials._table', compact('staff', 'educations'));
        }

        return view('pages.admin.administration.education.show.index', compact('staff', 'educations'));
    }

    public function create($id)
    {
        $staff = Data::findOrFail($id);
        $levels = EducationLevel::orderByRaw('CAST(level AS UNSIGNED) ASC')->get();

        return view('pages.admin.administration.education.show.partials._modal-form', compact('staff', 'levels'));
    }

    public function store(Request $request, $id)
    {
        $staff = Data::findOrFail($id);

        try {
            $validated = $request->validate($this->rules(), $this->messages(), $this->attributes());

            // Data pendidikan dan berkas disimpan dalam satu transaksi:
            // jika berkas ditolak, data pendidikan ikut dibatalkan.
            DB::transaction(function () use ($request, $staff, $validated) {
                $education = $staff->educations()->create(
                    $this->educationData($validated) + ['created_by' => Auth::id()]
                );

                $this->storeDocuments($education, $request, $validated);
            });
        } catch (ValidationException $e) {
            return $this->respondWithValidationError($request, $e);
        }

        // Jika HTMX request, kembalikan tabel ter-update
        if ($request->header('HX-Request')) {
            // Gunakan flash event untuk menutup modal, highlight data baru,
            // dan tampilkan notifikasi SweetAlert (samakan dengan pola Student)
            return response($this->show($request, $id)->render())
                ->header('HX-Trigger', json_encode([
                    'close-modal' => true,
                    'showAlert' => [
                        'icon'  => 'success',
                        'title' => 'Berhasil!',
                        'text'  => 'Riwayat pendidikan berhasil ditambahkan.',
                    ],
                ]));
        }

        return redirect()->route('admin.personnel.education.show', $id);
    }

    public function edit($staff_id, $edu_id)
    {
        $staff = Data::completed()->active()->findOrFail($staff_id);
        $education = $staff->educations()->findOrFail($edu_id);
        $levels = EducationLevel::orderByRaw('CAST(level AS UNSIGNED) ASC')->get();

        return view('pages.admin.administration.education.show.partials._modal-form', compact('staff', 'education', 'levels'));
    }

    public function update(Request $request, $staff_id, $edu_id)
    {
        $staff = Data::completed()->active()->findOrFail($staff_id);
        $education = $staff->educations()->findOrFail($edu_id);

        try {
            $validated = $request->validate($this->rules($education), $this->messages(), $this->attributes());

            DB::transaction(function () use ($request, $education, $validated) {
                $education->update(
                    $this->educationData($validated) + ['updated_by' => Auth::id()]
                );

                $this->syncDocumentMeta($education, $validated);
                $this->syncDocumentStatuses($education, $validated);
                $this->storeDocuments($education, $request, $validated);
            });
        } catch (ValidationException $e) {
            return $this->respondWithValidationError($request, $e);
        }

        if ($request->header('HX-Request')) {
            return response($this->show($request, $staff_id)->render())
                ->header('HX-Trigger', json_encode([
                    'close-modal' => true,
                    'showAlert' => [
                        'icon'  => 'success',
                        'title' => 'Berhasil!',
                        'text'  => 'Riwayat pendidikan berhasil diperbarui.',
                    ],
                ]));
        }

        return redirect()->route('admin.personnel.education.show', $staff_id);
    }

    /**
     * Hapus satu baris riwayat pendidikan milik seorang pegawai.
     * Perlu didaftarkan di routes, misal:
     * Route::delete('personnel/education/{staff_id}/{edu_id}', [EducationHistoryController::class, 'destroyEducation'])
     *     ->name('admin.personnel.education.destroy');
     */
    public function destroyEducation(Request $request, $staff_id, $edu_id)
    {
        // Pastikan pegawainya masih lengkap & active (sama seperti show()).
        // Dicek di luar try dan SEBELUM penghapusan supaya data tidak terhapus lalu 404.
        $staff = Data::completed()->active()->findOrFail($staff_id);

        try {
            $education = $staff->educations()->findOrFail($edu_id);
            $education->delete();
        } catch (\Throwable $e) {
            report($e);

            if ($request->header('HX-Request')) {
                return response($this->show($request, $staff_id)->render())
                    ->header('HX-Trigger', json_encode([
                        'showAlert' => [
                            'icon'  => 'error',
                            'title' => 'Gagal!',
                            'text'  => 'Riwayat pendidikan gagal dihapus. Silakan coba lagi.',
                        ],
                    ]));
            }

            return redirect()->route('admin.personnel.education.show', $staff_id)
                ->with('error', 'Riwayat pendidikan gagal dihapus.');
        }

        if ($request->header('HX-Request')) {
            return response($this->show($request, $staff_id)->render())
                ->header('HX-Trigger', json_encode([
                    'showAlert' => [
                        'icon'  => 'success',
                        'title' => 'Dihapus!',
                        'text'  => 'Riwayat pendidikan berhasil dihapus.',
                    ],
                ]));
        }

        return redirect()->route('admin.personnel.education.show', $staff_id);
    }

    /*
    |--------------------------------------------------------------------
    | Validasi & berkas
    |--------------------------------------------------------------------
    */

    /**
     * Aturan validasi bersama tambah & ubah (termasuk dua berkas PDF).
     *
     * Nomor & tanggal Transkrip Nilai (disimpan di baris dokumen) wajib bila:
     *  1. berkas transkrip baru diunggah, ATAU
     *  2. salah satunya diisi (nomor dan tanggal harus berpasangan), ATAU
     *  3. dokumen transkrip yang sudah ada memang sudah punya nilainya,
     *     sehingga tidak bisa dikosongkan lewat edit.
     * Kedua kolom dinonaktifkan di form bila belum ada berkas, jadi tidak
     * terkirim dan tidak divalidasi.
     *
     * @param EducationHistory|null $education Diisi saat ubah; null saat tambah.
     */
    private function rules(?EducationHistory $education = null): array
    {
        $pdf = ['nullable', 'file', 'mimes:pdf', 'max:' . StaffDocumentService::MAX_SIZE_KB];

        $existingTranscript = $education?->documents()
            ->whereHas('category', fn($c) => $c->where('code', 'transkrip_nilai'))
            ->latest()
            ->first();

        return [
            'education_level_id' => 'required',
            'institution_name' => 'required|string|max:255',
            'major' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'certificate_number' => 'required|string|max:255',
            'graduation_date' => 'nullable|date',
            'certificate_date' => 'required|date',
            'degree_name' => 'nullable|string|max:255',
            'degree_abbreviation' => 'nullable|string|max:255',
            'degree_position' => 'nullable|in:depan,belakang',
            'is_linear' => 'nullable|boolean',
            'file_ijazah' => $pdf,
            'file_transcript' => $pdf,
            self::TRANSCRIPT_FIELDS['number'] => [
                'nullable',
                'string',
                'max:255',
                'required_with:file_transcript,' . self::TRANSCRIPT_FIELDS['date'],
                Rule::requiredIf(filled($existingTranscript?->document_number)),
            ],
            self::TRANSCRIPT_FIELDS['date'] => [
                'nullable',
                'date',
                'required_with:file_transcript,' . self::TRANSCRIPT_FIELDS['number'],
                Rule::requiredIf(filled($existingTranscript?->document_date)),
            ],
            'status_ijazah' => 'nullable|in:draft,verified,rejected',
            'status_transcript' => 'nullable|in:draft,verified,rejected',
        ];
    }

    /**
     * Pesan error khusus (Bahasa Indonesia). Pesan error pertama yang
     * muncul ditampilkan lewat alert oleh respondWithValidationError().
     */
    private function messages(): array
    {
        return [
            // Data riwayat pendidikan
            'education_level_id.required' => 'Jenjang pendidikan wajib dipilih.',
            'institution_name.required' => 'Nama institusi wajib diisi.',
            'institution_name.max' => 'Nama institusi maksimal 255 karakter.',
            'certificate_number.required' => 'Nomor Ijazah wajib diisi.',
            'certificate_number.max' => 'Nomor Ijazah maksimal 255 karakter.',
            'certificate_date.required' => 'Tanggal Ijazah wajib diisi.',
            'certificate_date.date' => 'Tanggal Ijazah tidak valid.',
            'graduation_date.date' => 'Tanggal Lulus tidak valid.',
            'degree_position.in' => 'Posisi Gelar tidak valid.',

            // Berkas Ijazah
            'file_ijazah.mimes' => 'Berkas Ijazah harus berformat PDF.',
            'file_ijazah.max' => 'Ukuran berkas Ijazah maksimal 1 MB.',
            'file_ijazah.uploaded' => 'Berkas Ijazah gagal diunggah. Pastikan ukurannya maksimal 1 MB.',
            'status_ijazah.in' => 'Status berkas Ijazah tidak valid.',

            // Berkas Transkrip Nilai
            'file_transcript.mimes' => 'Berkas Transkrip Nilai harus berformat PDF.',
            'file_transcript.max' => 'Ukuran berkas Transkrip Nilai maksimal 1 MB.',
            'file_transcript.uploaded' => 'Berkas Transkrip Nilai gagal diunggah. Pastikan ukurannya maksimal 1 MB.',
            'status_transcript.in' => 'Status berkas Transkrip Nilai tidak valid.',

            // Nomor & tanggal Transkrip Nilai
            'transcript_number.required_with' => 'Nomor Transkrip wajib diisi jika mengunggah berkas atau mengisi Tanggal Transkrip.',
            'transcript_number.required' => 'Nomor Transkrip tidak boleh dikosongkan karena berkas Transkrip Nilai sudah memiliki nomor.',
            'transcript_number.max' => 'Nomor Transkrip maksimal 255 karakter.',
            'transcript_date.required_with' => 'Tanggal Transkrip wajib diisi jika mengunggah berkas atau mengisi Nomor Transkrip.',
            'transcript_date.required' => 'Tanggal Transkrip tidak boleh dikosongkan karena berkas Transkrip Nilai sudah memiliki tanggal.',
            'transcript_date.date' => 'Tanggal Transkrip tidak valid.',
        ];
    }

    /**
     * Nama field yang mudah dibaca. Dipakai pada pesan bawaan Laravel yang
     * tidak ditimpa di messages() (mis. string, boolean, dan aturan lain),
     * supaya tidak muncul nama mentah seperti "transcript number".
     */
    private function attributes(): array
    {
        return [
            'education_level_id' => 'Jenjang Pendidikan',
            'institution_name' => 'Nama Institusi',
            'major' => 'Jurusan',
            'province' => 'Provinsi',
            'certificate_number' => 'Nomor Ijazah',
            'certificate_date' => 'Tanggal Ijazah',
            'graduation_date' => 'Tanggal Lulus',
            'degree_name' => 'Nama Gelar',
            'degree_abbreviation' => 'Singkatan Gelar',
            'degree_position' => 'Posisi Gelar',
            'is_linear' => 'Linearitas',
            'file_ijazah' => 'Berkas Ijazah',
            'file_transcript' => 'Berkas Transkrip Nilai',
            'status_ijazah' => 'Status Ijazah',
            'status_transcript' => 'Status Transkrip Nilai',
            'transcript_number' => 'Nomor Transkrip',
            'transcript_date' => 'Tanggal Transkrip',
        ];
    }

    /**
     * Data untuk tabel riwayat pendidikan. Dibuang dari data tervalidasi:
     *  - field berkas (file_ijazah, file_transcript)
     *  - field status berkas (status_ijazah, status_transcript). Status ini
     *    milik dokumen, bukan kolom verification_status milik riwayat
     *    pendidikan (yang dipakai statistik kartu pendidikan).
     *  - nomor & tanggal transkrip (milik dokumen, bukan kolom riwayat)
     */
    private function educationData(array $validated): array
    {
        return Arr::except($validated, [
            ...array_keys(self::DOCUMENT_FIELDS),
            ...array_values(self::STATUS_FIELDS),
            ...array_values(self::TRANSCRIPT_FIELDS),
        ]);
    }

    /**
     * Status berkas yang sudah ada diperbarui sesuai pilihan di form (tanpa
     * perlu mengunggah berkas baru). Hanya status yang terkirim yang diubah.
     */
    private function syncDocumentStatuses(EducationHistory $education, array $validated): void
    {
        foreach (self::DOCUMENT_FIELDS as $field => $code) {
            $status = $validated[self::STATUS_FIELDS[$field]] ?? null;

            if (! filled($status)) {
                continue;
            }

            $education->documents()
                ->whereHas('category', fn($c) => $c->where('code', $code))
                ->update(['verification_status' => $status, 'updated_by' => Auth::id()]);
        }
    }

    /**
     * Metadata satu jenis berkas: nama, nomor, dan tanggal dokumen.
     *
     *  - document_name   : otomatis "{Kategori} {ALIAS jenjang}", mis. "Ijazah S1"
     *                      (lihat EducationHistory::documentName()).
     *  - Ijazah          : nomor & tanggal dari riwayat pendidikan
     *                      (certificate_number & certificate_date).
     *  - Transkrip Nilai : nomor & tanggal dari isian form
     *                      (transcript_number & transcript_date).
     */
    private function documentMeta(EducationHistory $education, string $code, array $validated): array
    {
        $meta = ['document_name' => $education->documentName($code)];

        if ($code === 'ijazah') {
            $meta['document_number'] = $education->certificate_number;
            $meta['document_date']   = $education->certificate_date;
        } else {
            $meta['document_number'] = $validated[self::TRANSCRIPT_FIELDS['number']] ?? null;
            $meta['document_date']   = $validated[self::TRANSCRIPT_FIELDS['date']] ?? null;
        }

        return $meta;
    }

    /**
     * Samakan metadata berkas yang SUDAH ada dengan isian form (dipanggil
     * dari update()). Per jenis berkas yang ada diperbarui: nama (ikut
     * berubah bila jenjang/institusi diubah), nomor, dan tanggal.
     *
     * Nilai kosong tidak dikirim, sehingga nomor/tanggal yang sudah
     * tersimpan tidak terhapus bila form tidak mengisinya (mis. dokumen
     * transkrip lama yang belum punya nomor tetap aman).
     */
    private function syncDocumentMeta(EducationHistory $education, array $validated): void
    {
        foreach (self::DOCUMENT_FIELDS as $code) {
            $attributes = array_filter(
                $this->documentMeta($education, $code, $validated),
                fn($value) => filled($value)
            );

            $education->documents()
                ->whereHas('category', fn($c) => $c->where('code', $code))
                ->update($attributes + ['updated_by' => Auth::id()]);
        }
    }

    /**
     * Simpan berkas yang diunggah (Ijazah dan/atau Transkrip Nilai). Satu
     * riwayat hanya punya satu berkas per jenis: berkas lama dengan jenis
     * yang sama dihapus lunak lalu digantikan.
     *
     * Setiap berkas disimpan dengan nama, nomor, dan tanggal dari
     * documentMeta(), serta status berkas dari form (tanpa status, service
     * memakai 'draft').
     *
     * Dipanggil SETELAH data riwayat tersimpan, jadi atribut $education
     * (jenjang, institusi, nomor & tanggal ijazah) sudah berisi nilai terbaru.
     */
    private function storeDocuments(EducationHistory $education, Request $request, array $validated = []): void
    {
        foreach (self::DOCUMENT_FIELDS as $field => $code) {
            if (! $request->hasFile($field)) {
                continue;
            }

            // Hapus lunak berkas lama dengan jenis yang sama
            $education->documents()
                ->whereHas('category', fn($c) => $c->where('code', $code))
                ->get()
                ->each->delete();

            $meta = $this->documentMeta($education, $code, $validated);

            $status = $validated[self::STATUS_FIELDS[$field]] ?? null;

            if (filled($status)) {
                $meta['verification_status'] = $status;
            }

            $education->attachDocument($request->file($field), $code, $meta);
        }
    }

    /**
     * Respons saat validasi gagal: modal TIDAK ditutup, pesan pertama
     * ditampilkan lewat alert (pola yang sama dengan modul lain).
     */
    private function respondWithValidationError(Request $request, ValidationException $e)
    {
        if ($request->header('HX-Request')) {
            $message = collect($e->errors())->flatten()->first() ?? 'Periksa kembali data yang dimasukkan.';

            return response('', 422)->header('HX-Trigger', json_encode([
                'showAlert' => [
                    'icon'  => 'error',
                    'title' => 'Gagal!',
                    'text'  => $message,
                ],
            ]));
        }

        throw $e;
    }
}
