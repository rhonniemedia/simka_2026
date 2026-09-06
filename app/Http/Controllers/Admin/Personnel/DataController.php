<?php

namespace App\Http\Controllers\Admin\Personnel;

use App\Enums\Staff\Gender;
use App\Enums\Staff\Religion;
use App\Enums\Staff\StaffStatus;
use App\Http\Controllers\Controller;
use App\Models\CoreConcentration;
use App\Models\Data;
use App\Models\EmploymentStatus;
use App\Models\PersonnelType;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DataController extends Controller
{
    public function index(Request $request)
    {
        // 1. Ambil parameter dari request
        $filterEmploymentStatus = $request->input('filter_employment_status');
        $filterPersonnel = $request->input('filter_personnel');
        $filterPosition = $request->input('filter_position');
        $filterGender = $request->input('filter_gender');
        $search = $request->input('search');

        // 2. Inisiasi Query beserta relasinya
        $query = Data::with(['vault', 'personnelType', 'grade', 'employmentStatus']);

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

        // 6. Siapkan Data Opsi untuk Select Filter
        $employmentOptions = DB::table('staff_employment_statuses')->pluck('name', 'id');
        $personnelOptions = DB::table('staff_personnel_types')->pluck('name', 'id');
        $positionOptions = DB::table('staff_positions')->pluck('name', 'id');

        // 6b. Statistik kartu
        $stats = $this->getStats();

        // 7. Render view parsial jika request datang dari HTMX
        if ($request->header('HX-Request')) {
            return view('pages.admin.personnel.data.partials._table', compact('staff'));
        }

        // 8. Render halaman utama penuh
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
        // Ubah referensi 'data' menjadi 'staff_data' sesuai dengan nama tabel di database
        $statuses = Data::leftJoin('staff_employment_statuses', 'staff_data.employment_id', '=', 'staff_employment_statuses.id')
            ->selectRaw('staff_employment_statuses.slug, count(staff_data.id) as total')
            ->groupBy('staff_employment_statuses.id', 'staff_employment_statuses.slug')
            ->pluck('total', 'slug');

        // Mengambil data berdasarkan slug 'pppk' dan 'pppk-pw'
        $pppk = $statuses->get('pppk', 0);
        $pppkPw = $statuses->get('pppk-pw', 0);

        return [
            'totalStats'   => Data::count(),
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
            $table = $this->index($request)->render();
            $statsOob = view('pages.admin.personnel.data.partials._stats-cards', array_merge(
                $this->getStats(),
                ['isOob' => true]
            ))->render();

            return $table . $statsOob;
        }

        return $this->index($request);
    }

    public function detailPersonal($id)
    {
        $staff = Data::with(['vault'])->findOrFail($id);
        return view('pages.admin.personnel.data.modals._detail-personal', compact('staff'));
    }

    public function detailEmployment($id)
    {
        $staff = Data::with(['personnelType', 'position'])->findOrFail($id);
        return view('pages.admin.personnel.data.modals._detail-employment', compact('staff'));
    }

    /*
    |--------------------------------------------------------------------
    | Create & Update
    |--------------------------------------------------------------------
    | Satu form (_edit-personal.blade.php) dipakai untuk create maupun
    | edit - sama seperti pola di modul Keluarga. Nama file "edit-personal"
    | dipertahankan karena sudah dipakai route yang ada, walau sekarang
    | isinya form gabungan (personal + kepegawaian + vault).
    */

    public function create()
    {
        return view(
            'pages.admin.personnel.data.partials._edit-personal',
            array_merge(['staff' => null], $this->buildFormOptions())
        );
    }

    public function store(Request $request)
    {
        $validated = $this->validateStaff($request);

        $staff = DB::transaction(function () use ($validated, $request) {
            $staff = Data::create([
                'name'                                => $validated['name'],
                'front_title'                         => $validated['front_title'] ?? null,
                'back_title'                           => $validated['back_title'] ?? null,
                'slug'                                 => $this->generateUniqueSlug($validated['name']),
                'employment_id'                        => $validated['employment_id'],
                'personnel_id'                         => $validated['personnel_id'],
                'position_id'                          => $validated['position_id'],
                'concentration_id'                     => $validated['concentration_id'] ?? null,
                'prior_service_period'                 => $validated['prior_service_period'] ?? null,
                'prior_service_period_effective_date'  => $validated['prior_service_period_effective_date'] ?? null,
                'gender'                               => $validated['gender'],
                'marital_dependents'                   => $validated['marital_dependents'] ?? null,
                'status'                               => $validated['status'],
                'status_effective_date'                => $validated['status_effective_date'] ?? null,
                'photo'                                => $this->handlePhotoUpload($request),
            ]);

            $staff->vault()->create($this->vaultPayload($validated));

            return $staff;
        });

        return $this->respondWithSuccess($request, 'Data pegawai berhasil ditambahkan.', $staff->id);
    }

    public function edit($id)
    {
        return $this->editPersonal($id);
    }

    public function editPersonal($id)
    {
        $staff = Data::with(['vault'])->findOrFail($id);

        return view(
            'pages.admin.personnel.data.partials._edit-personal',
            array_merge(compact('staff'), $this->buildFormOptions())
        );
    }

    public function update(Request $request, $id)
    {
        $staff = Data::with('vault')->findOrFail($id);
        $validated = $this->validateStaff($request, $id);

        DB::transaction(function () use ($staff, $validated, $request) {
            $staff->update([
                'name'                                => $validated['name'],
                'front_title'                         => $validated['front_title'] ?? null,
                'back_title'                           => $validated['back_title'] ?? null,
                'employment_id'                        => $validated['employment_id'],
                'personnel_id'                         => $validated['personnel_id'],
                'position_id'                          => $validated['position_id'],
                'concentration_id'                     => $validated['concentration_id'] ?? null,
                'prior_service_period'                 => $validated['prior_service_period'] ?? null,
                'prior_service_period_effective_date'  => $validated['prior_service_period_effective_date'] ?? null,
                'gender'                               => $validated['gender'],
                'marital_dependents'                   => $validated['marital_dependents'] ?? null,
                'status'                               => $validated['status'],
                'status_effective_date'                => $validated['status_effective_date'] ?? null,
                'photo'                                => $this->handlePhotoUpload($request, $staff->photo),
            ]);

            if ($staff->vault) {
                $staff->vault->update($this->vaultPayload($validated));
            } else {
                $staff->vault()->create($this->vaultPayload($validated));
            }
        });

        return $this->respondWithSuccess($request, 'Data pegawai berhasil diperbarui.', $staff->id);
    }

    /*
    |--------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------
    */

    private function buildFormOptions(): array
    {
        return [
            'employmentOptions'    => EmploymentStatus::orderBy('code')->get(),
            'personnelOptions'     => PersonnelType::orderBy('code')->get(),
            'positionOptions'      => Position::orderBy('code')->get(),
            'concentrationOptions' => class_exists(CoreConcentration::class) ? CoreConcentration::orderBy('name')->get() : collect(),
            'religionOptions'      => Religion::cases(),
            'genderOptions'        => Gender::cases(),
            'statusOptions'        => StaffStatus::cases(),
        ];
    }

    private function validateStaff(Request $request, ?string $ignoreStaffId = null): array
    {
        return $request->validate([
            'name'                                 => 'required|string|max:255',
            'front_title'                          => 'nullable|string|max:100',
            'back_title'                           => 'nullable|string|max:100',
            'gender'                               => ['required', Rule::enum(Gender::class)],
            'employment_id'                        => 'required|exists:staff_employment_statuses,id',
            'personnel_id'                         => 'required|exists:staff_personnel_types,id',
            'position_id'                          => 'required|exists:staff_positions,id',
            'concentration_id'                     => 'nullable|exists:core_concentrations,id',
            'prior_service_period'                 => 'nullable|string|max:255',
            'prior_service_period_effective_date'  => 'nullable|date',
            'marital_dependents'                   => 'nullable|string|max:50',
            'status'                               => ['required', Rule::enum(StaffStatus::class)],
            'status_effective_date'                => 'nullable|date',
            'photo'                                => 'nullable|image|max:2048',

            // Vault - lihat catatan uniqueVaultRule() soal kenapa NIK/NIP
            // tidak bisa pakai rule unique: bawaan Laravel (kolom yang
            // unique di database adalah *_hash, bukan nilai plaintext-nya).
            'nik'                                  => ['required', 'string', 'max:50', $this->uniqueVaultRule('nik_hash', $ignoreStaffId)],
            'nip'                                  => ['nullable', 'string', 'max:50', $this->uniqueVaultRule('nip_hash', $ignoreStaffId)],
            'nuptk'                                => 'nullable|string|max:50',
            'pob'                                  => 'nullable|string|max:255',
            'dob'                                  => 'nullable|date',
            'religion'                             => ['nullable', Rule::enum(Religion::class)],
            'npwp'                                 => 'nullable|string|max:50',
            'bank_account'                         => 'nullable|string|max:50',
            'base_salary'                          => 'nullable|numeric|min:0',
            'phone_number'                         => 'required|string|max:20',
            'email'                                => 'nullable|email|max:255',
            'address'                              => 'required|string|max:500',
            'rt'                                   => 'nullable|string|max:10',
            'rw'                                   => 'nullable|string|max:10',
            'village'                              => 'required|string|max:255',
            'district'                             => 'required|string|max:255',
            'regency'                              => 'required|string|max:255',
            'province'                             => 'required|string|max:255',
        ]);
    }

    /**
     * NIK/NIP unik secara fisik disimpan sebagai *_hash (SHA-256), bukan
     * nilai plaintext-nya - jadi rule bawaan Laravel `unique:table,column`
     * tidak bisa dipakai langsung (itu akan membandingkan NIK mentah
     * dengan kolom hash, yang tidak akan pernah cocok). Closure ini
     * meng-hash dulu nilai yang diinput baru dicocokkan ke kolom hash-nya.
     */
    private function uniqueVaultRule(string $hashColumn, ?string $ignoreStaffId): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) use ($hashColumn, $ignoreStaffId) {
            $hash = hash('sha256', trim($value));

            $exists = DB::table('staff_data_vault')
                ->where($hashColumn, $hash)
                ->when($ignoreStaffId, fn($q) => $q->where('staff_id', '!=', $ignoreStaffId))
                ->exists();

            if ($exists) {
                $label = strtoupper($attribute);
                $fail("{$label} ini sudah terdaftar untuk staff lain.");
            }
        };
    }

    private function vaultPayload(array $validated): array
    {
        return [
            'nik'          => $validated['nik'],
            'nip'          => $validated['nip'] ?? null,
            'nuptk'        => $validated['nuptk'] ?? null,
            'pob'          => $validated['pob'] ?? null,
            'dob'          => $validated['dob'] ?? null,
            'religion'     => $validated['religion'] ?? null,
            'npwp'         => $validated['npwp'] ?? null,
            'bank_account' => $validated['bank_account'] ?? null,
            'base_salary'  => $validated['base_salary'] ?? null,
            'phone_number' => $validated['phone_number'],
            'email'        => $validated['email'] ?? null,
            'address'      => $validated['address'],
            'rt'           => $validated['rt'] ?? null,
            'rw'           => $validated['rw'] ?? null,
            'village'      => $validated['village'],
            'district'     => $validated['district'],
            'regency'      => $validated['regency'],
            'province'     => $validated['province'],
        ];
    }

    private function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base !== '' ? $base : 'staff';
        $i = 1;

        while (Data::where('slug', $slug)->exists()) {
            $i++;
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    private function handlePhotoUpload(Request $request, ?string $existingPath = null): ?string
    {
        if (!$request->hasFile('photo')) {
            return $existingPath;
        }

        if ($existingPath) {
            Storage::disk('public')->delete($existingPath);
        }

        return $request->file('photo')->store('staff-photos', 'public');
    }

    private function respondWithSuccess(Request $request, string $message, string $staffId)
    {
        if ($request->header('HX-Request')) {
            $table = $this->index($request)->render();
            $statsOob = view('pages.admin.personnel.data.partials._stats-cards', array_merge(
                $this->getStats(),
                ['isOob' => true]
            ))->render();

            return response($table . $statsOob)
                ->header('HX-Trigger', json_encode([
                    'close-modal' => true,
                    'showAlert' => [
                        'icon'  => 'success',
                        'title' => 'Berhasil!',
                        'text'  => $message,
                    ],
                ]));
        }

        return redirect()->route('admin.personnel.data.index', ['highlight' => $staffId]);
    }
}
