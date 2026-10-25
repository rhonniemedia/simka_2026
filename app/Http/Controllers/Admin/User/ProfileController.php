<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /** Prefix view: resources/views/pages/admin/user/profile/... */
    private const VIEW = 'pages.admin.user.profile';

    // ========================================================================
    // READ
    // ========================================================================

    public function index(Request $request)
    {
        $user  = $request->user()->load('staff.vault');
        $staff = $user->staff;

        // Role yang ditampilkan = role di aplikasi SIMKA ini (bukan role di aplikasi lain).
        $roleName = $user->roles()
            ->wherePivot('app_id', config('app.core_id'))
            ->value('user_roles.name');

        $userData = [
            'name'      => $staff->name ?? $user->username,
            'email'     => $staff->vault->email ?? null,
            'telephone' => $staff->vault->phone_number ?? null,
            'nip'       => $staff->vault->nip ?? null,
            'role'      => $roleName ? ucwords($roleName) : '-',
            'photo'     => $staff?->photo ? asset('storage/' . $staff->photo) : null,
        ];

        return view(self::VIEW . '.index', compact('userData'));
    }

    // ========================================================================
    // UPDATE: FOTO
    // ========================================================================

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png|max:1024',
        ], [
            'photo.required' => 'Pilih foto terlebih dahulu.',
            'photo.image'    => 'File harus berupa gambar.',
            'photo.mimes'    => 'Foto harus berformat JPG atau PNG.',
            'photo.max'      => 'Ukuran foto maksimal 1MB.',
        ]);

        $staff = $request->user()->staff;

        if (! $staff) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data staf tidak ditemukan, foto tidak dapat disimpan.',
            ], 422);
        }

        $oldPhoto = $staff->photo;

        // Folder & batas ukuran sama dengan DataController::updatePhoto (staff-photos, 1 MB).
        // Simpan foto baru dulu; foto lama baru dihapus setelah data berhasil diperbarui.
        $path = $request->file('photo')->store('staff-photos', 'public');
        $staff->update([
            'photo'      => $path,
            'updated_by' => $request->user()->id,
        ]);

        if ($oldPhoto && Storage::disk('public')->exists($oldPhoto)) {
            Storage::disk('public')->delete($oldPhoto);
        }

        return response()->json([
            'status'    => 'success',
            'message'   => 'Foto profil berhasil diperbarui.',
            'photo_url' => asset('storage/' . $path),
        ]);
    }

    // ========================================================================
    // UPDATE: DATA PROFIL (nomor telepon)
    // ========================================================================

    public function editData(Request $request)
    {
        $user = $request->user()->load('staff.vault');

        return view(self::VIEW . '.partials._modal-edit-data', compact('user'));
    }

    public function updateData(Request $request)
    {
        $user = $request->user()->load('staff.vault');

        // Hapus spasi agar "0812 3456 7890" tetap diterima.
        $request->merge(['telephone' => preg_replace('/\s+/', '', (string) $request->input('telephone'))]);

        $validator = Validator::make($request->all(), [
            'telephone' => 'required|numeric|digits_between:10,15',
        ], [
            'telephone.required'       => 'Nomor telepon wajib diisi.',
            'telephone.numeric'        => 'Nomor telepon hanya boleh berisi angka.',
            'telephone.digits_between' => 'Nomor telepon harus terdiri dari 10 hingga 15 digit.',
        ]);

        $validator->after(function ($v) use ($user) {
            if (! $user->staff?->vault) {
                $v->errors()->add('telephone', 'Data kontak staf tidak ditemukan. Hubungi administrator.');
            }
        });

        if ($validator->fails()) {
            $request->flash(); // agar isian yang salah tidak hilang saat modal dirender ulang

            return view(self::VIEW . '.partials._modal-edit-data', compact('user'))
                ->withErrors($validator->errors());
        }

        $user->staff->vault->update([
            'phone_number' => $request->input('telephone'),
            'updated_by'   => $user->id,
        ]);

        return response('')->header('HX-Trigger', json_encode([
            'profileUpdated' => [
                'telephone' => $request->input('telephone'),
                'message'   => 'Data profil berhasil diperbarui.',
            ],
        ]));
    }

    // ========================================================================
    // UPDATE: KATA SANDI
    // ========================================================================

    public function editPassword(Request $request)
    {
        $user = $request->user();

        return view(self::VIEW . '.partials._modal-edit-password', compact('user'));
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'current_password'],
            'new_password'     => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'current_password.required'         => 'Kata sandi saat ini wajib diisi.',
            'current_password.current_password' => 'Kata sandi tidak cocok dengan data kami.',
            'new_password.required'             => 'Kata sandi baru wajib diisi.',
            'new_password.min'                  => 'Kata sandi baru minimal 8 karakter.',
            'new_password.confirmed'            => 'Konfirmasi sandi tidak cocok.',
            'new_password.different'            => 'Kata sandi baru tidak boleh sama dengan kata sandi saat ini.',
        ]);

        if ($validator->fails()) {
            // Sengaja tidak memanggil $request->flash(): kata sandi tidak boleh ikut tersimpan di session.
            return view(self::VIEW . '.partials._modal-edit-password', compact('user'))
                ->withErrors($validator->errors());
        }

        // Otomatis di-hash oleh cast 'password' => 'hashed' pada model User
        $user->update(['password' => $request->input('new_password')]);

        return response('')->header('HX-Trigger', json_encode([
            'passwordUpdated' => [
                'message' => 'Kata sandi Anda berhasil diperbarui.',
            ],
        ]));
    }
}
