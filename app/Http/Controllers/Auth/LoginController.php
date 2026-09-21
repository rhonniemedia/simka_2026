<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('pages.auth.login');
    }

    public function authenticate(Request $request)
    {
        $request->validate([
            'login_id' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $loginId = trim($request->input('login_id'));
        $password = $request->input('password');
        $user = null;

        // 1. Identifikasi Jenis Input (Email, NIP, atau Username)
        if (filter_var($loginId, FILTER_VALIDATE_EMAIL)) {
            $emailHash = hash('sha256', strtolower($loginId));
            $user = User::whereHas('staff.vault', function ($query) use ($emailHash) {
                $query->where('email_hash', $emailHash);
            })->first();
        } elseif (preg_match('/^[0-9]{10,20}$/', $loginId)) {
            $nipHash = hash('sha256', $loginId);
            $user = User::whereHas('staff.vault', function ($query) use ($nipHash) {
                $query->where('nip_hash', $nipHash);
            })->first();
        } else {
            $user = User::where('username', $loginId)->first();
        }

        // 2. Verifikasi Password
        if ($user && Hash::check($password, $user->password)) {

            // 3. Verifikasi Akses Aplikasi & Role
            $appIdSekarang = config('app.core_id');

            // Ambil data role spesifik pengguna untuk aplikasi ini
            $roleAplikasi = $user->roles()->wherePivot('app_id', $appIdSekarang)->first();

            // Jika tidak ada data di tabel pivot (tidak punya akses sama sekali)
            if (!$roleAplikasi) {
                return back()->withErrors([
                    'login_id' => 'Akun Anda valid, tetapi tidak memiliki otoritas untuk mengakses aplikasi ini.',
                ])->withInput($request->only('login_id', 'remember')); // <--- Tambahkan withInput di sini
            }

            // Jika punya akses, tapi role-nya hanya sebagai 'user'
            if ($roleAplikasi->name === 'user') {
                // Tampilkan halaman dilarang akses tanpa membuat session login
                return view('pages.auth.forbidden');
            }

            // 4. Eksekusi Login (Hanya jika role adalah admin atau superadmin)
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            // Arahkan ke dashboard admin
            return redirect()->intended(route('admin.home'));
        }

        return back()->withErrors([
            'login_id' => 'Kredensial yang diberikan tidak cocok dengan data kami.',
        ])->withInput($request->only('login_id', 'remember')); // <--- Tambahkan withInput di sini juga  ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
