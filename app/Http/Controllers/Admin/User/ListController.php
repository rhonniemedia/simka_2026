<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller;
use App\Models\Data;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Daftar Pengguna SIMKA.
 *
 * Tabel `users` dipakai bersama aplikasi lain (mis. Pintar), sedangkan hak akses
 * per aplikasi disimpan di `user_app_roles`. Karena itu:
 *  - "Tambah"  : membuat akun baru ATAU memberi akses SIMKA ke akun yang sudah ada.
 *  - "Cabut"   : hanya mencabut akses SIMKA (akun & akses di aplikasi lain tidak disentuh).
 *  - users.status bersifat global dan tidak diubah dari sini.
 */
class ListController extends Controller
{
    /** Prefix view: resources/views/pages/admin/user/list/... */
    private const VIEW = 'pages.admin.user.list';

    /**
     * Role yang bisa diberikan. Role 'user' disiapkan untuk halaman pengguna
     * (nanti); untuk saat ini LoginController & middleware app.access masih
     * menolak akun yang hanya punya role 'user'.
     */
    private const ASSIGNABLE_ROLES = ['superadmin', 'admin', 'user'];

    /** Role yang boleh diberikan oleh admin biasa (superadmin hanya oleh superadmin). */
    private const ADMIN_ASSIGNABLE_ROLES = ['admin', 'user'];

    /** Role yang ditampilkan di filter daftar. */
    private const LISTED_ROLES = ['superadmin', 'admin', 'user'];

    // ========================================================================
    // HELPER: KONFIGURASI & RESPONS
    // ========================================================================

    /** ID aplikasi SIMKA dari .env (CORE_APP_ID) lewat config('app.core_id'). */
    protected function appId(): string
    {
        $appId = config('app.core_id');

        abort_if(blank($appId), 500, 'CORE_APP_ID belum diatur di file .env');

        return (string) $appId;
    }

    protected function defaultPassword(string $phone): string
    {
        return 'MySch' . $phone . '*';
    }

    protected function alert(string $icon, string $title, string $text): array
    {
        return ['icon' => $icon, 'title' => $title, 'text' => $text];
    }

    /** Respons 204 + HX-Trigger. */
    protected function hxTrigger(array $events)
    {
        return response()->noContent()->header('HX-Trigger', json_encode($events));
    }

    // ========================================================================
    // HELPER: OTORISASI (superadmin hanya boleh dikelola superadmin)
    // ========================================================================

    protected function hasRoleInApp(User $user, string $roleName): bool
    {
        return $user->roles()
            ->wherePivot('app_id', $this->appId())
            ->where('user_roles.name', $roleName)
            ->exists();
    }

    protected function isSuperadmin(Request $request): bool
    {
        return $this->hasRoleInApp($request->user(), 'superadmin');
    }

    /**
     * Admin biasa tidak boleh mengubah/mereset/mencabut akun superadmin
     * (kalau boleh, admin bisa menaikkan hak aksesnya lewat reset sandi).
     * Mengembalikan respons penolakan, atau null bila diizinkan.
     */
    protected function denyIfNotManageable(Request $request, User $user)
    {
        if (! $this->isSuperadmin($request) && $this->hasRoleInApp($user, 'superadmin')) {
            return $this->hxTrigger([
                'showAlert' => $this->alert('error', 'Tidak Diizinkan', 'Akun superadmin hanya dapat dikelola oleh superadmin.'),
            ]);
        }

        return null;
    }

    /** Role yang boleh diberikan oleh pengguna yang sedang login. */
    protected function assignableRoleNames(Request $request): array
    {
        return $this->isSuperadmin($request) ? self::ASSIGNABLE_ROLES : self::ADMIN_ASSIGNABLE_ROLES;
    }

    protected function assignableRoles(Request $request)
    {
        return UserRole::whereIn('name', $this->assignableRoleNames($request))->orderBy('name')->get();
    }

    protected function assignableRoleRule(Request $request)
    {
        $names = $this->assignableRoleNames($request);

        return Rule::exists('user_roles', 'id')->where(fn($q) => $q->whereIn('name', $names));
    }

    // ========================================================================
    // HELPER: DATA
    // ========================================================================

    /** Hapus role user di app ini, lalu simpan 1 role yang dipilih. */
    protected function assignRole(string $userId, string $appId, string $roleId): void
    {
        DB::table('user_app_roles')
            ->where('user_id', $userId)
            ->where('app_id', $appId)
            ->delete();

        DB::table('user_app_roles')->insert([
            'id'         => (string) Str::uuid(),
            'user_id'    => $userId,
            'app_id'     => $appId,
            'role_id'    => $roleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Staf yang bisa diberi akses SIMKA: data lengkap (bukan draft), aktif,
     * punya data vault, dan belum punya role di SIMKA. Boleh sudah punya akun
     * dari aplikasi lain (akun itu hanya diberi akses).
     *
     * Nomor telepon tersimpan terenkripsi (phone_number_encrypted, wajib diisi),
     * jadi tidak perlu difilter di sini.
     */
    protected function availableStaff()
    {
        $appId = $this->appId();

        return Data::completed()
            ->where('staff_data.status', 'active')
            ->whereHas('vault')
            ->whereNotIn('staff_data.id', User::query()
                ->whereNotNull('staff_id')
                ->whereHas('roles', fn($q) => $q->where('user_app_roles.app_id', $appId))
                ->select('staff_id'))
            ->orderBy('name');
    }

    protected function createFormData(Request $request): array
    {
        $staff = $this->availableStaff()->get(['staff_data.id', 'staff_data.name', 'staff_data.front_title', 'staff_data.back_title']);

        // staf_id => username, hanya untuk staf yang sudah punya akun (username bukan data rahasia).
        $accounts = User::whereIn('staff_id', $staff->pluck('id'))->pluck('username', 'staff_id')->all();

        $options = $staff->map(fn($s) => [
            'value' => $s->id,
            'label' => $s->name_with_title,
            'group' => isset($accounts[$s->id]) ? 'Sudah punya akun (aplikasi lain)' : 'Belum punya akun',
        ])->sortBy(fn($o) => $o['group'] === 'Belum punya akun' ? 0 : 1)->values()->all();

        $roles = $this->assignableRoles($request);

        return [
            'staffOptions'     => $options,
            'existingAccounts' => (object) $accounts,
            'roles'            => $roles,
            'defaultRoleId'    => $roles->firstWhere('name', 'admin')?->id,
        ];
    }

    protected function usernameRules(?string $ignoreId = null): array
    {
        $unique = Rule::unique('users', 'username');
        if ($ignoreId) {
            $unique->ignore($ignoreId);
        }

        return [
            'required',
            'string',
            'min:3',
            'max:50',
            'regex:/^[A-Za-z0-9._-]+$/',
            // Login menganggap angka 10-20 digit sebagai NIP, bukan username.
            'not_regex:/^[0-9]{10,20}$/',
            $unique,
        ];
    }

    protected function messages(): array
    {
        return [
            'required'            => ':attribute wajib diisi.',
            'unique'              => ':attribute sudah digunakan.',
            'regex'               => ':attribute hanya boleh berisi huruf, angka, titik, garis bawah, dan strip.',
            'username.not_regex'  => 'Username tidak boleh seluruhnya berupa angka 10-20 digit karena akan dikenali sebagai NIP saat login.',
            'min'                 => ':attribute minimal :min karakter.',
            'max'                 => ':attribute maksimal :max karakter.',
            'exists'              => ':attribute tidak valid atau tidak boleh Anda berikan.',
        ];
    }

    protected function attributes(): array
    {
        return [
            'staff_id' => 'Staf',
            'username' => 'Username',
            'role_id'  => 'Role',
        ];
    }

    /** Hitung user di app ini, opsional per nama role dan/atau status akun. */
    protected function countUsers(string $appId, ?array $roleNames = null, ?string $status = null): int
    {
        return User::query()
            ->when($status, fn($q) => $q->where('users.status', $status))
            ->whereHas('roles', function ($q) use ($appId, $roleNames) {
                $q->where('user_app_roles.app_id', $appId);
                if ($roleNames) {
                    $q->whereIn('user_roles.name', $roleNames);
                }
            })
            ->count();
    }

    protected function stats(string $appId): array
    {
        return [
            'totalUsers'      => $this->countUsers($appId),
            'superadminStats' => $this->countUsers($appId, ['superadmin']),
            'adminStats'      => $this->countUsers($appId, ['admin']),
            'userStats'       => $this->countUsers($appId, ['user']),
        ];
    }

    // ========================================================================
    // READ
    // ========================================================================

    public function index(Request $request)
    {
        $search     = $request->input('search');
        $roleFilter = $request->input('role');
        $appId      = $this->appId();

        $roles         = UserRole::whereIn('name', self::LISTED_ROLES)->orderBy('name')->get();
        $isSuperadmin  = $this->isSuperadmin($request);
        $currentUserId = (string) $request->user()->getKey();

        // Hanya user yang punya role di aplikasi SIMKA ini.
        $query = User::query()
            ->whereHas('roles', fn($q) => $q->where('user_app_roles.app_id', $appId))
            ->with([
                'staff.vault',
                'roles' => fn($q) => $q->where('user_app_roles.app_id', $appId),
            ])
            ->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', '%' . $search . '%')
                    ->orWhereHas('staff', function ($staffQuery) use ($search) {
                        $staffQuery->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($roleFilter) {
            $query->whereHas('roles', function ($roleQuery) use ($roleFilter, $appId) {
                $roleQuery->where('name', $roleFilter)
                    ->where('user_app_roles.app_id', $appId);
            });
        }

        $data  = $query->paginate(10)->appends($request->query());
        $stats = $this->stats($appId);

        if ($request->header('HX-Request')) {
            // Tabel + kartu statistik (out-of-band) ikut diperbarui dalam satu respons.
            return response(
                view(self::VIEW . '.partials._table', compact('data', 'search', 'isSuperadmin', 'currentUserId'))->render()
                    . view(self::VIEW . '.partials._stats-cards', $stats + ['isOob' => true])->render()
            );
        }

        return view(self::VIEW . '.index', compact('data', 'search', 'roles', 'isSuperadmin', 'currentUserId') + $stats);
    }

    // ========================================================================
    // CREATE (buat akun baru ATAU beri akses SIMKA ke akun yang sudah ada)
    // ========================================================================

    public function create(Request $request)
    {
        return view(self::VIEW . '.partials._modal-create', $this->createFormData($request));
    }

    public function store(Request $request)
    {
        $request->merge(['username' => trim((string) $request->input('username'))]);

        // Staf yang sudah punya akun (mis. dari Pintar): akun dipakai apa adanya.
        $existing = User::where('staff_id', (string) $request->input('staff_id'))->first();
        $staff    = null;

        $validator = Validator::make($request->all(), [
            'staff_id' => ['required', 'string'],
            'username' => $existing ? ['nullable'] : $this->usernameRules(),
            'role_id'  => ['required', $this->assignableRoleRule($request)],
        ], $this->messages(), $this->attributes());

        $validator->after(function ($v) use ($request, $existing, &$staff) {
            if ($v->errors()->has('staff_id')) {
                return;
            }

            $staff = $this->availableStaff()->find($request->input('staff_id'));

            if (! $staff) {
                $v->errors()->add('staff_id', 'Staf tidak ditemukan, belum lengkap, tidak aktif, atau sudah memiliki akses SIMKA.');

                return;
            }

            if ($existing && $existing->status !== 'active') {
                $v->errors()->add('staff_id', 'Akun staf ini berstatus nonaktif sehingga tidak dapat diberi akses.');
            }

            if (! $existing && blank($staff->vault?->phone_number)) {
                $v->errors()->add('staff_id', 'Nomor telepon staf belum terdata, kata sandi awal tidak bisa dibuat.');
            }
        });

        if ($validator->fails()) {
            $request->flash();

            return view(self::VIEW . '.partials._modal-create', $this->createFormData($request))
                ->withErrors($validator->errors());
        }

        $validated = $validator->validated();
        $appId     = $this->appId();
        $password  = null;
        $username  = $existing?->username ?? $validated['username'];

        DB::transaction(function () use ($existing, $staff, $validated, $appId, &$password) {
            if ($existing) {
                $userId = $existing->id;
            } else {
                $password = $this->defaultPassword($staff->vault->phone_number);

                $userId = User::create([
                    'staff_id' => $staff->id,
                    'username' => $validated['username'],
                    'password' => $password, // di-hash otomatis oleh cast 'hashed'
                    'status'   => 'active',
                ])->id;
            }

            $this->assignRole($userId, $appId, $validated['role_id']);
        });

        // Kata sandi awal hanya ditampilkan sekali, di alert ini.
        $text = $existing
            ? "Akun {$username} diberi akses SIMKA. Username dan kata sandi tidak diubah."
            : "Akun {$username} berhasil dibuat. Kata sandi awal: {$password}";

        return $this->hxTrigger([
            'close-modal'  => true,
            'refreshTable' => true,
            'showAlert'    => $this->alert('success', 'Berhasil!', $text),
        ]);
    }

    // ========================================================================
    // UPDATE (username)
    // ========================================================================

    public function edit(Request $request, User $user)
    {
        if ($denied = $this->denyIfNotManageable($request, $user)) {
            return $denied;
        }

        $user->load('staff');

        return view(self::VIEW . '.partials._modal-edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        if ($denied = $this->denyIfNotManageable($request, $user)) {
            return $denied;
        }

        $request->merge(['username' => trim((string) $request->input('username'))]);

        $validator = Validator::make($request->all(), [
            'username' => $this->usernameRules($user->id),
        ], $this->messages(), $this->attributes());

        if ($validator->fails()) {
            $request->flash();
            $user->load('staff');

            return view(self::VIEW . '.partials._modal-edit', compact('user'))
                ->withErrors($validator->errors());
        }

        $user->update(['username' => $validator->validated()['username']]);

        return $this->hxTrigger([
            'close-modal'  => true,
            'refreshTable' => true,
            'showAlert'    => $this->alert('success', 'Berhasil!', 'Username berhasil diperbarui.'),
        ]);
    }

    // ========================================================================
    // DELETE (cabut akses SIMKA; akun tidak dihapus)
    // ========================================================================

    public function revokeAccess(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return $this->hxTrigger([
                'showAlert' => $this->alert('error', 'Gagal!', 'Anda tidak dapat mencabut akses akun Anda sendiri.'),
            ]);
        }

        if ($denied = $this->denyIfNotManageable($request, $user)) {
            return $denied;
        }

        DB::table('user_app_roles')
            ->where('user_id', $user->id)
            ->where('app_id', $this->appId())
            ->delete();

        return $this->hxTrigger([
            'refreshTable' => true,
            'showAlert'    => $this->alert(
                'success',
                'Berhasil!',
                "Akses SIMKA untuk {$user->username} dicabut. Akunnya tidak dihapus."
            ),
        ]);
    }

    // ========================================================================
    // ROLE
    // ========================================================================

    public function editRole(Request $request, User $user)
    {
        if ($denied = $this->denyIfNotManageable($request, $user)) {
            return $denied;
        }

        $appId = $this->appId();
        $roles = $this->assignableRoles($request);

        $selectedRoleId = $user->roles()
            ->wherePivot('app_id', $appId)
            ->value('user_roles.id');

        return view(self::VIEW . '.partials._modal-edit-role', compact('user', 'roles', 'selectedRoleId'));
    }

    public function updateRole(Request $request, User $user)
    {
        if ($denied = $this->denyIfNotManageable($request, $user)) {
            return $denied;
        }

        // Mencegah kehilangan akses sendiri (mis. superadmin terakhir menurunkan dirinya).
        if ($user->is($request->user())) {
            return $this->hxTrigger([
                'showAlert' => $this->alert('error', 'Gagal!', 'Anda tidak dapat mengubah role Anda sendiri.'),
            ]);
        }

        $validated = $request->validate([
            'role_id' => ['required', $this->assignableRoleRule($request)],
        ], $this->messages(), $this->attributes());

        $appId  = $this->appId();
        $roleId = $validated['role_id'];

        DB::transaction(fn() => $this->assignRole($user->id, $appId, $roleId));

        return $this->hxTrigger([
            'close-modal'  => true,
            'refreshTable' => true,
            'showAlert'    => $this->alert('success', 'Berhasil!', 'Hak akses pengguna berhasil diperbarui.'),
        ]);
    }

    // ========================================================================
    // RESET PASSWORD (default tetap: MySch{telepon}*)
    // ========================================================================

    public function editPassword(Request $request, User $user)
    {
        if ($denied = $this->denyIfNotManageable($request, $user)) {
            return $denied;
        }

        $phone = optional($user->staff?->vault)->phone_number;

        return view(self::VIEW . '.partials._modal-edit-password', compact('user', 'phone'));
    }

    public function updatePassword(Request $request, User $user)
    {
        if ($denied = $this->denyIfNotManageable($request, $user)) {
            return $denied;
        }

        $phone = optional($user->staff?->vault)->phone_number;

        if (! $phone) {
            return $this->hxTrigger([
                'showAlert' => $this->alert(
                    'error',
                    'Gagal!',
                    'Nomor telepon pengguna belum terdata, kata sandi tidak bisa direset ke default.'
                ),
            ]);
        }

        // Otomatis di-hash oleh cast 'password' => 'hashed' pada model User
        $user->update(['password' => $this->defaultPassword($phone)]);

        return $this->hxTrigger([
            'close-modal' => true,
            'showAlert'   => $this->alert('success', 'Berhasil!', 'Kata sandi pengguna berhasil direset ke default.'),
        ]);
    }
}
