<?php

namespace App\Http\Controllers\Admin\Personnel;

use App\Enums\Staff\Gender;
use App\Enums\Staff\Religion;
use App\Http\Controllers\Controller;
use App\Models\CoreConcentration;
use App\Models\Data;
use App\Models\EmploymentStatus;
use App\Models\PersonnelType;
use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DataController extends Controller
{
    /*
    |--------------------------------------------------------------------
    | Peta step form
    |--------------------------------------------------------------------
    | Form Data Pegawai terdiri dari 4 step dan setiap step disimpan ke
    | database begitu tombol "Selanjutnya" ditekan:
    |
    |   1. Pribadi          -> staff_data + vault   (membuat draft)
    |   2. Kepegawaian      -> staff_data + vault
    |   3. Kontak & Alamat  -> vault
    |   4. Finansial        -> vault                (opsional; menutup draft)
    |
    | Selama step 4 belum disimpan, baris staff_data berstatus
    | is_draft = true dan TIDAK tampil di daftar maupun statistik.
    */

    private const TOTAL_STEPS = 4;

    /** Draft yang tidak disentuh selama N hari dibuang otomatis. */
    private const STALE_DRAFT_DAYS = 7;

    private const FORM_VIEW = 'pages.admin.personnel.data.partials._edit-personal';
    private const DETAIL_PERSONAL_VIEW = 'pages.admin.personnel.data.partials._detail-personal';
    private const DETAIL_EMPLOYMENT_VIEW = 'pages.admin.personnel.data.partials._detail-employment';

    /** Kolom staff_data yang diisi pada tiap step. */
    private const STAFF_FIELDS = [
        1 => ['name', 'front_title', 'back_title', 'gender', 'marital_dependents'],
        2 => [
            'employment_id',
            'personnel_id',
            'position_id',
            'concentration_id',
            'prior_service_period',
            'prior_service_period_effective_date',
            'status_effective_date',
        ],
    ];

    /** Atribut vault yang diisi pada tiap step (dienkripsi oleh model DataVault). */
    private const VAULT_FIELDS = [
        1 => ['nik', 'pob', 'dob', 'religion'],
        2 => ['nip', 'nuptk'],
        3 => ['phone_number', 'email', 'address', 'rt', 'rw', 'village', 'district', 'regency', 'province'],
        4 => ['npwp', 'bank_account', 'base_salary'],
    ];

    /*
    |--------------------------------------------------------------------
    | Daftar, statistik, hapus, detail
    |--------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        // 1. Ambil parameter dari request
        $filterEmploymentStatus = $request->input('filter_employment_status');
        $filterPersonnel = $request->input('filter_personnel');
        $filterPosition = $request->input('filter_position');
        $filterGender = $request->input('filter_gender');
        $search = $request->input('search');

        // 2. Inisiasi query beserta relasinya. Draft yang belum selesai tidak ditampilkan.
        $query = Data::completed()->with(['vault', 'personnelType', 'grade', 'employmentStatus']);

        // 3. Pencarian (nama & hash NIK/NIP/NUPTK)
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

        // 4. Filter
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

        // 5. Paginasi (berdasarkan abjad)
        $staff = $query->orderBy('name', 'asc')->paginate(10)->withQueryString();

        // 6. Opsi untuk select filter
        $employmentOptions = DB::table('staff_employment_statuses')->orderBy('code')->pluck('name', 'id');
        $personnelOptions = DB::table('staff_personnel_types')->orderBy('code')->pluck('name', 'id');
        $positionOptions = DB::table('staff_positions')->orderBy('code')->pluck('name', 'id');

        // 6b. Statistik kartu
        $stats = $this->getStats();

        // 7. View parsial jika request datang dari HTMX
        if ($request->header('HX-Request')) {
            return view('pages.admin.personnel.data.partials._table', compact('staff'));
        }

        // 8. Halaman utama penuh
        return view('pages.admin.personnel.data.index', array_merge(
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
        $statuses = Data::completed()
            ->leftJoin('staff_employment_statuses', 'staff_data.employment_id', '=', 'staff_employment_statuses.id')
            ->selectRaw('staff_employment_statuses.slug, count(staff_data.id) as total')
            ->groupBy('staff_employment_statuses.id', 'staff_employment_statuses.slug')
            ->pluck('total', 'slug');

        $pppk = $statuses->get('pppk', 0);
        $pppkPw = $statuses->get('pppk-pw', 0);

        return [
            'totalStats'   => Data::completed()->count(),
            'pnsStats'     => $statuses->get('pns', 0),
            'pppkTotal'    => $pppk + $pppkPw,
            'pppkPenuh'    => $pppk,
            'pppkParuh'    => $pppkPw,
            'honorerStats' => $statuses->get('honorer', 0),
        ];
    }

    public function destroy(Request $request, $id)
    {
        $staff = Data::findOrFail($id);
        $staff->delete();

        if ($request->header('HX-Request')) {
            return $this->tableWithStatsOob($request);
        }

        return $this->index($request);
    }

    public function detailPersonal($id)
    {
        $staff = Data::with(['vault', 'employmentStatus', 'position'])->findOrFail($id);

        return view(self::DETAIL_PERSONAL_VIEW, compact('staff'));
    }

    public function detailEmployment($id)
    {
        $staff = Data::with(['vault', 'employmentStatus', 'personnelType', 'position'])->findOrFail($id);

        $concentration = class_exists(CoreConcentration::class) && filled($staff->concentration_id)
            ? CoreConcentration::find($staff->concentration_id)
            : null;

        return view(self::DETAIL_EMPLOYMENT_VIEW, compact('staff', 'concentration'));
    }

    /*
    |--------------------------------------------------------------------
    | Form: tampilkan modal
    |--------------------------------------------------------------------
    */

    /**
     * Modal Tambah Data. Kalau user ini masih punya draft yang belum
     * selesai, form dibuka kembali di step yang belum tuntas.
     */
    public function create()
    {
        $this->pruneStaleDrafts();

        return $this->renderForm($this->findOwnDraft());
    }

    public function edit($id)
    {
        return $this->editPersonal($id);
    }

    public function editPersonal($id)
    {
        return $this->renderForm(Data::with('vault')->findOrFail($id));
    }

    /**
     * Modal Upload/Ganti Foto Pegawai.
     */
    public function editPhoto($id)
    {
        $staff = Data::findOrFail($id);

        return view('pages.admin.personnel.data.partials._edit-photo-modal', compact('staff'));
    }

    private function renderForm(?Data $staff)
    {
        [$startStep, $maxStep] = $this->resolveSteps($staff);

        return view(self::FORM_VIEW, array_merge(
            ['staff' => $staff, 'startStep' => $startStep, 'maxStep' => $maxStep],
            $this->buildFormOptions()
        ));
    }

    /**
     * @return array{0:int,1:int} [step awal saat modal dibuka, step tertinggi yang boleh dibuka lewat stepper]
     */
    private function resolveSteps(?Data $staff): array
    {
        if (!$staff) {
            return [1, 1];
        }

        // Data lengkap (mode edit): semua step boleh dibuka bebas.
        if (!$staff->is_draft) {
            return [1, self::TOTAL_STEPS];
        }

        // Draft: lanjutkan dari step pertama yang belum tuntas.
        $next = $this->firstIncompleteStep($staff);

        return [$next, $next];
    }

    /**
     * Step pertama (2, 3, atau 4) yang datanya belum lengkap. Step 1 selalu
     * dianggap tuntas karena barisnya baru ada setelah step 1 disimpan.
     * Nilai 4 berarti step 2 dan 3 sudah lengkap dan tinggal step 4.
     */
    private function firstIncompleteStep(Data $staff): int
    {
        $vault = $staff->vault;

        $step2Done = filled($staff->employment_id)
            && filled($staff->personnel_id)
            && filled($staff->position_id)
            && filled($staff->status_effective_date)
            && $staff->prior_service_period !== null;

        if (!$step2Done) {
            return 2;
        }

        $step3Done = $vault
            && filled($vault->phone_number_encrypted)
            && filled($vault->address_encrypted)
            && filled($vault->village_encrypted)
            && filled($vault->district_encrypted)
            && filled($vault->regency_encrypted)
            && filled($vault->province_encrypted);

        return $step3Done ? 4 : 3;
    }

    private function buildFormOptions(): array
    {
        return [
            'employmentOptions'    => EmploymentStatus::orderBy('code')->get(),
            'personnelOptions'     => PersonnelType::orderBy('code')->get(),
            'positionOptions'      => Position::orderBy('code')->get(),
            'concentrationOptions' => class_exists(CoreConcentration::class) ? CoreConcentration::orderBy('name')->get() : collect(),
            'religionOptions'      => Religion::cases(),
            'genderOptions'        => Gender::cases(),
        ];
    }

    /*
    |--------------------------------------------------------------------
    | Simpan per step
    |--------------------------------------------------------------------
    */

    /**
     * Step 1 untuk data BARU: membuat baris staff_data (draft) + vault.
     * Respons JSON berisi staff_id yang dipakai form untuk step berikutnya.
     */
    public function storeStep(Request $request): JsonResponse
    {
        try {
            if ($this->resolveStep($request) !== 1) {
                throw ValidationException::withMessages([
                    '_form' => 'Pengisian data pegawai baru harus dimulai dari langkah 1.',
                ]);
            }

            $validated = $this->validateStep($request, 1);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        }

        $userId = Auth::id();

        $staff = DB::transaction(function () use ($validated, $userId) {
            $staff = Data::create($this->staffAttributesFor(1, $validated) + [
                'slug'       => $this->generateUniqueSlug($validated['name']),
                'is_draft'   => true,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $staff->vault()->create($this->vaultAttributesFor(1, $validated) + [
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            return $staff;
        });

        return response()->json([
            'ok'       => true,
            'staff_id' => $staff->id,
            'step'     => 1,
            'message'  => 'Langkah 1 tersimpan.',
        ], 201);
    }

    /**
     * Simpan satu step untuk data yang sudah ada (draft maupun edit).
     *
     * - Step 1..3 : respons JSON, form lanjut ke step berikutnya.
     * - Step 4    : menutup draft (bila ada) dan mengembalikan tabel + statistik
     *               untuk HTMX, sama seperti alur simpan yang lama.
     */
    public function updateStep(Request $request, string $id)
    {
        $staff = Data::with('vault')->findOrFail($id);

        try {
            $step = $this->resolveStep($request);
            $isFinalStep = $step === self::TOTAL_STEPS;
            $wasDraft = (bool) $staff->is_draft;

            // Semua atribut vault (kecuali NIK) baru bisa diisi setelah step 1 membuat barisnya.
            if (!$staff->vault && $step !== 1) {
                throw ValidationException::withMessages([
                    '_form' => 'Data identitas (langkah 1) belum tersimpan. Kembali ke langkah 1 terlebih dahulu.',
                ]);
            }

            // Draft baru boleh ditutup kalau step 2 dan 3 benar-benar sudah tersimpan.
            if ($isFinalStep && $wasDraft) {
                $incomplete = $this->firstIncompleteStep($staff);
                if ($incomplete < self::TOTAL_STEPS) {
                    throw ValidationException::withMessages([
                        '_form' => "Data pada langkah {$incomplete} belum lengkap. Kembali ke langkah tersebut lalu simpan.",
                    ]);
                }
            }

            $validated = $this->validateStep($request, $step, $staff->id);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        }

        $userId = Auth::id();

        DB::transaction(function () use ($staff, $step, $validated, $isFinalStep, $userId) {
            $staffData = $this->staffAttributesFor($step, $validated);
            $vaultData = $this->vaultAttributesFor($step, $validated);

            if ($isFinalStep) {
                $staffData['is_draft'] = false;
            }

            $staff->update($staffData + ['updated_by' => $userId]);

            if ($vaultData !== []) {
                if ($staff->vault) {
                    $staff->vault->update($vaultData + ['updated_by' => $userId]);
                } else {
                    $staff->vault()->create($vaultData + ['created_by' => $userId, 'updated_by' => $userId]);
                }
            }

            $staff->touch();
        });

        if (!$isFinalStep) {
            return response()->json([
                'ok'       => true,
                'staff_id' => $staff->id,
                'step'     => $step,
                'message'  => "Langkah {$step} tersimpan.",
            ]);
        }

        return $this->respondWithSuccess(
            $request,
            $wasDraft ? 'Data pegawai berhasil ditambahkan.' : 'Data pegawai berhasil diperbarui.',
            $staff->id
        );
    }

    /**
     * Simpan/ganti foto pegawai. Foto lama (jika ada) dihapus dari storage
     * setelah foto baru berhasil disimpan.
     */
    public function updatePhoto(Request $request, string $id)
    {
        $staff = Data::findOrFail($id);

        try {
            \Illuminate\Support\Facades\Validator::make(
                $request->all(),
                [
                    'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:1024'],
                ],
                [
                    'required' => ':attribute wajib diunggah.',
                    'image'    => ':attribute harus berupa gambar.',
                    'mimes'    => ':attribute harus berformat JPG atau PNG.',
                    'max'      => ':attribute maksimal 1 MB.',
                ],
                [
                    'photo' => 'Foto',
                ]
            )->validate();
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        }

        $oldPhoto = $staff->photo;

        $path = $request->file('photo')->store('staff-photos', 'public');

        $staff->photo = $path;
        $staff->updated_by = Auth::id();
        $staff->save();

        if ($oldPhoto && \Illuminate\Support\Facades\Storage::disk('public')->exists($oldPhoto)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPhoto);
        }

        return $this->respondWithSuccess($request, 'Foto pegawai berhasil diperbarui.', $staff->id);
    }

    /**
     * Buang draft yang belum selesai (tombol "Mulai dari awal" di modal).
     * Hanya draft yang bisa dibuang lewat rute ini, data lengkap tidak.
     */
    public function discardDraft(string $id): JsonResponse
    {
        $staff = Data::draft()->findOrFail($id);

        $this->deleteDraft($staff);

        return response()->json(['ok' => true]);
    }

    /*
    |--------------------------------------------------------------------
    | Draft
    |--------------------------------------------------------------------
    */

    private function findOwnDraft(): ?Data
    {
        $userId = Auth::id();

        if (!$userId) {
            return null;
        }

        return Data::draft()
            ->with('vault')
            ->where('created_by', $userId)
            ->latest('updated_at')
            ->first();
    }

    /**
     * Draft yang ditinggalkan menahan NIK/NIP-nya (kolom hash unik), jadi
     * yang sudah lama tidak disentuh dibuang agar tidak menumpuk.
     */
    private function pruneStaleDrafts(): void
    {
        Data::draft()
            ->where('updated_at', '<', now()->subDays(self::STALE_DRAFT_DAYS))
            ->get()
            ->each(fn(Data $draft) => $this->deleteDraft($draft));
    }

    private function deleteDraft(Data $staff): void
    {
        DB::transaction(function () use ($staff) {
            $staff->vault()->delete();
            $staff->delete();
        });
    }

    /*
    |--------------------------------------------------------------------
    | Validasi per step
    |--------------------------------------------------------------------
    */

    private function resolveStep(Request $request): int
    {
        $step = (int) $request->input('step');

        if ($step < 1 || $step > self::TOTAL_STEPS) {
            throw ValidationException::withMessages(['_form' => 'Langkah form tidak valid.']);
        }

        return $step;
    }

    private function validateStep(Request $request, int $step, ?string $ignoreStaffId = null): array
    {
        return \Illuminate\Support\Facades\Validator::make(
            $request->all(),
            $this->stepRules($step, $ignoreStaffId),
            $this->validationMessages(),
            $this->validationAttributes()
        )->validate();
    }

    private function stepRules(int $step, ?string $ignoreStaffId = null): array
    {
        return match ($step) {
            1 => [
                'name'               => ['required', 'string', 'max:255'],
                'front_title'        => ['nullable', 'string', 'max:100'],
                'back_title'         => ['nullable', 'string', 'max:100'],
                'gender'             => ['required', Rule::enum(Gender::class)],
                'nik'                => ['required', 'string', 'regex:/^\d{16}$/', $this->uniqueVaultRule('nik_hash', $ignoreStaffId)],
                'pob'                => ['nullable', 'string', 'max:255'],
                'dob'                => ['nullable', 'date'],
                'religion'           => ['nullable', Rule::enum(Religion::class)],
                'marital_dependents' => ['nullable', 'string', 'max:50'],
            ],

            2 => [
                'employment_id'                       => ['required', 'exists:staff_employment_statuses,id'],
                'personnel_id'                        => ['required', 'exists:staff_personnel_types,id'],
                'position_id'                         => ['required', 'exists:staff_positions,id'],
                'concentration_id'                    => ['nullable', 'exists:core_concentrations,id'],
                'nip'                                 => ['nullable', 'string', 'regex:/^\d{15,20}$/', $this->uniqueVaultRule('nip_hash', $ignoreStaffId)],
                'nuptk'                               => ['nullable', 'string', 'max:50'],
                'status_effective_date'               => ['required', 'date'],
                'prior_service_period'                => ['required', Rule::in(['0', '1'])],
                'prior_service_period_effective_date' => ['nullable', 'date'],
            ],

            3 => [
                'phone_number' => ['required', 'string', 'regex:/^[0-9]{10,15}$/', $this->uniqueVaultRule('phone_number_hash', $ignoreStaffId)],
                'email'        => ['nullable', 'email', 'max:255', $this->uniqueVaultRule('email_hash', $ignoreStaffId)],
                'address'      => ['required', 'string', 'max:500'],
                'rt'           => ['nullable', 'string', 'max:10'],
                'rw'           => ['nullable', 'string', 'max:10'],
                'village'      => ['required', 'string', 'max:255'],
                'district'     => ['required', 'string', 'max:255'],
                'regency'      => ['required', 'string', 'max:255'],
                'province'     => ['required', 'string', 'max:255'],
            ],

            4 => [
                'npwp'         => ['nullable', 'string', 'regex:/^\d{15,16}$/'],
                'bank_account' => ['nullable', 'string', 'regex:/^\d{8,20}$/'],
                'base_salary'  => ['nullable', 'numeric', 'min:0'],
            ],
        };
    }

    /**
     * NIK/NIP unik secara fisik disimpan sebagai *_hash (SHA-256), bukan
     * nilai plaintext-nya, jadi rule bawaan `unique:table,column` tidak
     * bisa dipakai. Closure ini meng-hash input dulu lalu mencocokkannya
     * ke kolom hash. Bila yang bentrok ternyata masih draft, pesannya
     * dibedakan supaya jelas kenapa datanya tidak terlihat di daftar.
     */
    private function uniqueVaultRule(string $hashColumn, ?string $ignoreStaffId): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) use ($hashColumn, $ignoreStaffId) {
            $hash = hash('sha256', trim((string) $value));

            $conflict = DB::table('staff_data_vault as v')
                ->join('staff_data as s', 's.id', '=', 'v.staff_id')
                ->where("v.{$hashColumn}", $hash)
                ->when($ignoreStaffId, fn($q) => $q->where('v.staff_id', '!=', $ignoreStaffId))
                ->select('s.is_draft')
                ->first();

            if (!$conflict) {
                return;
            }

            $label = $this->validationAttributes()[$attribute] ?? $attribute;

            $fail($conflict->is_draft
                ? "{$label} ini sedang dipakai pada draft data pegawai yang belum selesai."
                : "{$label} ini sudah terdaftar untuk pegawai lain.");
        };
    }

    private function validationMessages(): array
    {
        return [
            'required'     => ':attribute wajib diisi.',
            'string'       => ':attribute harus berupa teks.',
            'max.string'   => ':attribute maksimal :max karakter.',
            'date'         => ':attribute bukan tanggal yang valid.',
            'email.email'  => 'Format :attribute tidak valid.',
            'numeric'      => ':attribute harus berupa angka.',
            'min.numeric'  => ':attribute minimal :min.',
            'exists'       => ':attribute yang dipilih tidak valid.',
            'enum'         => ':attribute yang dipilih tidak valid.',
            'in'           => ':attribute yang dipilih tidak valid.',
            'nik.regex'          => 'NIK harus berisi tepat 16 digit angka.',
            'nip.regex'          => 'NIP harus berisi 15 hingga 20 digit angka.',
            'phone_number.regex' => 'Nomor telepon tidak valid (10-15 angka).',
            'npwp.regex'         => 'NPWP harus 15 atau 16 digit angka.',
            'bank_account.regex' => 'Nomor rekening harus 8 hingga 20 digit angka.',
        ];
    }

    private function validationAttributes(): array
    {
        return [
            'name'                                => 'Nama lengkap',
            'front_title'                         => 'Gelar depan',
            'back_title'                          => 'Gelar belakang',
            'gender'                              => 'Jenis kelamin',
            'nik'                                 => 'NIK',
            'pob'                                 => 'Tempat lahir',
            'dob'                                 => 'Tanggal lahir',
            'religion'                            => 'Agama',
            'marital_dependents'                  => 'Status kawin & tanggungan',
            'employment_id'                       => 'Status kepegawaian',
            'personnel_id'                        => 'Jenis pegawai',
            'position_id'                         => 'Jabatan',
            'concentration_id'                    => 'Konsentrasi',
            'nip'                                 => 'NIP',
            'nuptk'                               => 'NUPTK',
            'status_effective_date'               => 'Tanggal masuk / TMT status',
            'prior_service_period'                => 'Peninjauan masa kerja',
            'prior_service_period_effective_date' => 'TMT masa kerja',
            'phone_number'                        => 'Nomor telepon',
            'email'                               => 'Email',
            'address'                             => 'Alamat',
            'rt'                                  => 'RT',
            'rw'                                  => 'RW',
            'village'                             => 'Desa / kelurahan',
            'district'                            => 'Kecamatan',
            'regency'                             => 'Kabupaten / kota',
            'province'                            => 'Provinsi',
            'npwp'                                => 'NPWP',
            'bank_account'                        => 'Nomor rekening',
            'base_salary'                         => 'Gaji pokok',
        ];
    }

    /*
    |--------------------------------------------------------------------
    | Pemetaan hasil validasi -> atribut model
    |--------------------------------------------------------------------
    | Hanya key yang benar-benar dikirim pada step tersebut yang ikut
    | disimpan, jadi field yang tidak ada di form (mis. gelar) tidak
    | tertimpa jadi NULL setiap kali data disimpan.
    */

    private function staffAttributesFor(int $step, array $validated): array
    {
        $data = $this->blankToNull(Arr::only($validated, self::STAFF_FIELDS[$step] ?? []));

        // Tanpa peninjauan masa kerja, tanggal TMT-nya tidak boleh tersisa.
        if (($data['prior_service_period'] ?? null) === '0') {
            $data['prior_service_period_effective_date'] = null;
        }

        return $data;
    }

    private function vaultAttributesFor(int $step, array $validated): array
    {
        return $this->blankToNull(Arr::only($validated, self::VAULT_FIELDS[$step] ?? []));
    }

    private function blankToNull(array $data): array
    {
        return array_map(fn($value) => $value === '' ? null : $value, $data);
    }

    private function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $base = $base !== '' ? $base : 'staff';
        $slug = $base;
        $i = 1;

        while (Data::where('slug', $slug)->exists()) {
            $i++;
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    /*
    |--------------------------------------------------------------------
    | Respons
    |--------------------------------------------------------------------
    */

    private function tableWithStatsOob(Request $request): string
    {
        $table = $this->index($request)->render();
        $statsOob = view('pages.admin.personnel.data.partials._stats-cards', array_merge(
            $this->getStats(),
            ['isOob' => true]
        ))->render();

        return $table . $statsOob;
    }

    private function respondWithSuccess(Request $request, string $message, string $staffId)
    {
        if ($request->header('HX-Request')) {
            return response($this->tableWithStatsOob($request))
                ->header('HX-Trigger', json_encode([
                    'close-modal' => true,
                    'showAlert'   => [
                        'icon'  => 'success',
                        'title' => 'Berhasil!',
                        'text'  => $message,
                    ],
                ]));
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'finished' => true, 'staff_id' => $staffId, 'message' => $message]);
        }

        return redirect()->route('admin.personnel.data.index', ['highlight' => $staffId]);
    }

    private function validationErrorResponse(ValidationException $e): JsonResponse
    {
        return response()->json([
            'message' => $e->getMessage(),
            'errors'  => $e->errors(),
        ], $e->status);
    }
}
