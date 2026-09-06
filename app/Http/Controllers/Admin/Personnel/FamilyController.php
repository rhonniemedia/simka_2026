<?php

namespace App\Http\Controllers\Admin\Personnel;

use App\Enums\Staff\FamilyRelation as FamilyRelationEnum;
use App\Enums\Staff\Gender;
use App\Enums\Staff\Profession;
use App\Http\Controllers\Controller;
use App\Models\Data;
use App\Models\Data as Staff;
use App\Models\DataVault;
use App\Models\FamilyMember;
use App\Models\FamilyRelation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FamilyController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        // Disesuaikan dengan value pada method show (husband, wife)
        $spouseRelations = ['husband', 'wife'];

        $query = Staff::with(['familyRelations' => function ($qRelation) use ($spouseRelations) {
            // Memastikan data diambil dari relasi familyMember
            $qRelation->whereIn('relationship', $spouseRelations)->with('familyMember');
        }])
            ->when($search, function ($q) use ($search, $spouseRelations) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('familyRelations', function ($qRelation) use ($search, $spouseRelations) {
                        // Penting: filter relationship DAN nama
                        // familyMember harus dicek pada baris relasi yang SAMA
                        $qRelation->whereIn('relationship', $spouseRelations)
                            ->whereHas('familyMember', function ($qMember) use ($search) {
                                $qMember->where('name', 'like', "%{$search}%");
                            });
                    });
            })
            ->latest();

        $staffs = $query->paginate(10)->withQueryString();

        if ($request->header('HX-Request')) {
            return view('pages.admin.personnel.family.partials._table', compact('staffs'));
        }

        return view('pages.admin.personnel.family.index', compact('staffs', 'search'));
    }

    public function show(Request $request, $id)
    {
        $staff = Data::with(['vault', 'employmentStatus'])->findOrFail($id);

        $families = $staff->familyRelations()
            ->with([
                'familyMember' => function ($q) {
                    $q->withCount('relations')->with(['educationLevel', 'linkedStaff']);
                },
            ])
            ->orderByRaw("FIELD(relationship, 'husband', 'wife', 'child', 'other')")
            ->paginate(10);

        if ($request->header('HX-Request')) {
            return view('pages.admin.personnel.family.show.partials._table', compact('staff', 'families'));
        }

        return view('pages.admin.personnel.family.show.index', compact('staff', 'families'));
    }

    public function create(Request $request, $id)
    {
        $staff = Data::findOrFail($id);

        [$prefill, $linkToFamilyMemberId] = $this->resolvePrefillFromRequest($request);

        // ?refresh=1 dikirim oleh tombol "Isi Otomatis"/"Tautkan" di
        // _nik-check-result.blade.php - modalnya SUDAH terbuka, jadi cukup
        // muat ulang isi form (#family-modal-content), JANGAN shell modal
        // penuh (lihat catatan di _modal-form.blade.php).
        $view = $request->boolean('refresh')
            ? 'pages.admin.personnel.family.show.partials._modal-form-content'
            : 'pages.admin.personnel.family.show.partials._modal-form';

        return view($view, [
            'staff'                => $staff,
            'prefill'              => $prefill,
            'linkToFamilyMemberId' => $linkToFamilyMemberId,
        ]);
    }

    /**
     * Endpoint dipanggil (via HTMX) saat admin selesai mengetik NIK di form.
     * Cek ke DUA tabel:
     * - staff_family_members : NIK ini sudah pernah didaftarkan sebagai
     *   anggota keluarga siapa? (untuk alur Tautkan / Buat Baru)
     * - staff_data_vault     : NIK ini ternyata NIK-nya seorang staff?
     *   (untuk peringatan potensi tunjangan dobel, walau orang ini belum
     *   pernah didaftarkan sebagai anggota keluarga sama sekali)
     */
    public function checkNik(Request $request, $id)
    {
        $staff = Data::findOrFail($id);
        $nik = trim((string) $request->input('nik'));

        // Kalau sedang edit, jangan anggap match dengan dirinya sendiri.
        $ignoreRelationId = $request->input('ignore_relation_id');

        // Dipakai partial hasil cek untuk tahu harus reload ke route
        // create (tambah baru) atau edit (sedang mengedit relasi ini),
        // supaya tombol "Tautkan" / "Isi Otomatis" muat ulang form yang
        // benar lewat server, bukan menyuntik value pakai JS.
        $relation = $ignoreRelationId
            ? $staff->familyRelations()->find($ignoreRelationId)
            : null;

        if (strlen($nik) < 6) {
            return response('');
        }

        $existing = FamilyMember::whereNik($nik)
            ->with(['relations' => function ($q) {
                $q->with('staff');
            }])
            ->first();

        $matchedVault = DataVault::whereNik($nik)->with(['staff.highestEducation'])->first();
        $matchedStaff = $matchedVault?->staff;

        if (!$existing && !$matchedStaff) {
            return response('');
        }

        $alreadyLinkedToThisStaff = $existing
            ? $existing->relations
            ->where('id', '!=', $ignoreRelationId)
            ->contains(fn(FamilyRelation $relation) => $relation->staff_id === $staff->id)
            : false;

        return view('pages.admin.personnel.family.show.partials._nik-check-result', [
            'staff'                    => $staff,
            'relation'                 => $relation,
            'existing'                 => $existing,
            'matchedStaff'             => $matchedStaff,
            'matchedVault'             => $matchedVault,
            'alreadyLinkedToThisStaff' => $alreadyLinkedToThisStaff,
        ]);
    }

    public function store(Request $request, $id)
    {
        $staff = Data::findOrFail($id);

        $validated = $request->validate([
            'name'                     => 'required|string|max:255',
            'relationship'             => ['required', Rule::enum(FamilyRelationEnum::class)],
            'gender'                   => ['required', Rule::enum(Gender::class)],
            'nik'                      => 'nullable|string|max:50',
            'birth_place'              => 'nullable|string|max:255',
            'birth_date'               => 'nullable|date',
            'telephone'                => 'nullable|string|max:255',
            'occupation'               => ['nullable', Rule::enum(Profession::class)],
            'education_level_id'       => 'nullable|exists:staff_education_levels,id',
            'is_studying'              => 'nullable|boolean',
            'marriage_date_encrypted'  => 'nullable|date',
            'link_to_family_member_id' => 'nullable|uuid|exists:staff_family_members,id',
        ]);

        $familyMember = $this->resolveFamilyMember($staff, $validated);

        if ($familyMember === null) {
            return $this->respondWithError($request, $id, 'NIK ini sudah terdaftar sebagai anggota keluarga staff ini juga.');
        }

        $staff->familyRelations()->create([
            'family_member_id'        => $familyMember->id,
            'relationship'             => $validated['relationship'],
            'family_relation_code'     => FamilyRelationEnum::from($validated['relationship'])->dapodikCode(),
            'marriage_date_encrypted'  => $validated['marriage_date_encrypted'] ?? null,
        ]);

        if ($request->header('HX-Request')) {
            return response($this->show($request, $id)->render())
                ->header('HX-Trigger', json_encode([
                    'close-modal' => true,
                    'showAlert' => [
                        'icon'  => 'success',
                        'title' => 'Berhasil!',
                        'text'  => 'Data anggota keluarga berhasil ditambahkan.',
                    ],
                ]));
        }

        return redirect()->route('admin.personnel.family.show', $id);
    }

    public function edit(Request $request, $staff_id, $family_id)
    {
        $staff = Data::findOrFail($staff_id);
        $relation = $staff->familyRelations()->with('familyMember')->findOrFail($family_id);

        [$prefill, $linkToFamilyMemberId] = $this->resolvePrefillFromRequest($request);

        $view = $request->boolean('refresh')
            ? 'pages.admin.personnel.family.show.partials._modal-form-content'
            : 'pages.admin.personnel.family.show.partials._modal-form';

        return view($view, [
            'staff'                => $staff,
            'relation'             => $relation,
            'prefill'              => $prefill,
            'linkToFamilyMemberId' => $linkToFamilyMemberId,
        ]);
    }

    public function update(Request $request, $staff_id, $family_id)
    {
        $staff = Data::findOrFail($staff_id);
        $relation = $staff->familyRelations()->with('familyMember')->findOrFail($family_id);

        $validated = $request->validate([
            'name'                    => 'required|string|max:255',
            'relationship'            => ['required', Rule::enum(FamilyRelationEnum::class)],
            'gender'                  => ['required', Rule::enum(Gender::class)],
            'nik'                     => 'nullable|string|max:50',
            'birth_place'             => 'nullable|string|max:255',
            'birth_date'              => 'nullable|date',
            'telephone'               => 'nullable|string|max:255',
            'occupation'              => ['nullable', Rule::enum(Profession::class)],
            'education_level_id'      => 'nullable|exists:staff_education_levels,id',
            'is_studying'             => 'nullable|boolean',
            'marriage_date_encrypted' => 'nullable|date',
        ]);

        // Catatan: data orang (nama/NIK/dll) ini dipakai bersama kalau
        // family_member-nya ditautkan ke lebih dari satu staff. Mengedit
        // dari sini akan ikut mengubah data yang dilihat staff lain juga -
        // sudah diberi peringatan di form (lihat _modal-form.blade.php).
        $relation->familyMember->update([
            'name'               => $validated['name'],
            'gender'             => $validated['gender'],
            'nik'                => $validated['nik'] ?? null,
            'birth_place'        => $validated['birth_place'] ?? null,
            'birth_date'         => $validated['birth_date'] ?? null,
            'telephone'          => $validated['telephone'] ?? null,
            'occupation'         => $validated['occupation'] ?? null,
            'education_level_id' => $validated['education_level_id'] ?? null,
            'is_studying'        => $validated['is_studying'] ?? null,
        ]);

        $this->refreshLinkedStaff($relation->familyMember, $validated['nik'] ?? null);

        $relation->update([
            'relationship'            => $validated['relationship'],
            'family_relation_code'    => FamilyRelationEnum::from($validated['relationship'])->dapodikCode(),
            'marriage_date_encrypted' => $validated['marriage_date_encrypted'] ?? null,
        ]);

        if ($request->header('HX-Request')) {
            return response($this->show($request, $staff_id)->render())
                ->header('HX-Trigger', json_encode([
                    'close-modal' => true,
                    'showAlert' => [
                        'icon'  => 'success',
                        'title' => 'Berhasil!',
                        'text'  => 'Data anggota keluarga berhasil diperbarui.',
                    ],
                ]));
        }

        return redirect()->route('admin.personnel.family.show', $staff_id);
    }

    public function destroy(Request $request, $staff_id, $family_id)
    {
        try {
            $staff = Data::findOrFail($staff_id);
            $relation = $staff->familyRelations()->findOrFail($family_id);
            $familyMember = $relation->familyMember;

            $relation->delete();

            // Hapus data orangnya juga HANYA kalau tidak ada staff lain
            // yang masih menautkan orang ini.
            if ($familyMember && $familyMember->relations()->doesntExist()) {
                $familyMember->delete();
            }
        } catch (\Throwable $e) {
            report($e);

            if ($request->header('HX-Request')) {
                return response($this->show($request, $staff_id)->render())
                    ->header('HX-Trigger', json_encode([
                        'showAlert' => [
                            'icon'  => 'error',
                            'title' => 'Gagal!',
                            'text'  => 'Data keluarga gagal dihapus. Silakan coba lagi.',
                        ],
                    ]));
            }
            return redirect()->route('admin.personnel.family.show', $staff_id);
        }

        if ($request->header('HX-Request')) {
            return response($this->show($request, $staff_id)->render())
                ->header('HX-Trigger', json_encode([
                    'showAlert' => [
                        'icon'  => 'success',
                        'title' => 'Dihapus!',
                        'text'  => 'Data keluarga berhasil dihapus.',
                    ],
                ]));
        }

        return redirect()->route('admin.personnel.family.show', $staff_id);
    }

    /*
    |--------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------
    */

    /**
     * Membaca query string ?link_to_family_member_id=... atau
     * ?prefill_from_staff_nik=... lalu mengembalikan [$prefill, $linkToFamilyMemberId]
     * untuk dipakai modal-form.blade.php mengisi field secara SERVER-SIDE
     * (lewat atribut :value Blade), bukan lewat JavaScript yang menyuntik
     * .value ke komponen dropdown custom (yang sering tidak update teks
     * yang ditampilkan walau value aslinya sudah benar).
     *
     * - link_to_family_member_id  : dari tombol "Tautkan" - $prefill berupa
     *   FamilyMember yang sudah ada, dan relasi baru akan memakainya
     *   langsung (lihat resolveFamilyMember()).
     * - prefill_from_staff_nik    : dari tombol "Isi Otomatis dari Data
     *   Staff Ini" - $prefill berupa objek sementara (bukan model
     *   tersimpan) hasil gabungan staff_data_vault + riwayat pendidikan
     *   staff. Tidak menautkan apa pun, cuma mengisi form.
     */
    private function resolvePrefillFromRequest(Request $request): array
    {
        if ($linkId = $request->query('link_to_family_member_id')) {
            $familyMember = FamilyMember::find($linkId);

            return [$familyMember, $familyMember?->id];
        }

        if ($vaultNik = $request->query('prefill_from_staff_nik')) {
            $matchedVault = DataVault::whereNik($vaultNik)->with(['staff.highestEducation'])->first();
            $matchedStaff = $matchedVault?->staff;

            if ($matchedStaff) {
                // Objek "palsu" (bukan model FamilyMember tersimpan),
                // sengaja dibuat mirip supaya field Blade di modal-form
                // (mis. $familyMember->name) tetap bisa dipakai apa adanya.
                $prefill = (object) [
                    'nik'                => $vaultNik,
                    'name'               => $matchedStaff->name,
                    'gender'             => $matchedStaff->gender,
                    'telephone'          => $matchedVault->phone_number,
                    'birth_place'        => $matchedVault->pob,
                    'birth_date'         => $matchedVault->dob,
                    'education_level_id' => $matchedStaff->highestEducation->education_level_id ?? null,
                    // Sengaja dikosongkan: pekerjaan staff beda konteks
                    // dengan pekerjaan anggota keluarga, harus dipilih manual.
                    'occupation'         => null,
                    'is_studying'        => null,
                ];

                return [$prefill, null];
            }
        }

        return [null, null];
    }

    /**
     * Menentukan FamilyMember mana yang dipakai untuk relasi baru:
     * - kalau admin memilih "Tautkan" (link_to_family_member_id terisi),
     *   pakai FamilyMember yang sudah ada - JANGAN buat data orang baru.
     * - kalau tidak, buat FamilyMember baru dari input form.
     *
     * Return null kalau staff ini ternyata SUDAH menautkan orang tersebut
     * sebelumnya (mencegah relasi duplikat).
     */
    private function resolveFamilyMember(Staff $staff, array $validated): ?FamilyMember
    {
        $linkId = $validated['link_to_family_member_id'] ?? null;

        if ($linkId) {
            $familyMember = FamilyMember::findOrFail($linkId);

            $alreadyLinked = $staff->familyRelations()
                ->where('family_member_id', $familyMember->id)
                ->exists();

            if ($alreadyLinked) {
                return null;
            }

            return $familyMember;
        }

        $familyMember = FamilyMember::create([
            'name'               => $validated['name'],
            'gender'             => $validated['gender'],
            'nik'                => $validated['nik'] ?? null,
            'birth_place'        => $validated['birth_place'] ?? null,
            'birth_date'         => $validated['birth_date'] ?? null,
            'telephone'          => $validated['telephone'] ?? null,
            'occupation'         => $validated['occupation'] ?? null,
            'education_level_id' => $validated['education_level_id'] ?? null,
            'is_studying'        => $validated['is_studying'] ?? null,
        ]);

        $this->refreshLinkedStaff($familyMember, $validated['nik'] ?? null);

        return $familyMember;
    }

    /**
     * Cek apakah NIK anggota keluarga ini cocok dengan NIK seorang staff
     * (staff_data_vault). Kalau cocok, simpan referensinya di
     * linked_staff_id - dipakai untuk peringatan potensi tunjangan dobel.
     */
    private function refreshLinkedStaff(FamilyMember $familyMember, ?string $nik): void
    {
        $matchedStaffId = $nik
            ? DataVault::whereNik($nik)->value('staff_id')
            : null;

        $familyMember->update(['linked_staff_id' => $matchedStaffId]);
    }

    private function respondWithError(Request $request, $staffId, string $message)
    {
        if ($request->header('HX-Request')) {
            return response($this->show($request, $staffId)->render())
                ->header('HX-Trigger', json_encode([
                    'showAlert' => [
                        'icon'  => 'error',
                        'title' => 'Tidak Bisa Ditambahkan',
                        'text'  => $message,
                    ],
                ]));
        }

        return redirect()->route('admin.personnel.family.show', $staffId)->withErrors(['nik' => $message]);
    }
}
