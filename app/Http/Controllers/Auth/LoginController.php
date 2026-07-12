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
        return view('auth.login');
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
            $user = User::where('email_hash', $emailHash)->first();
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

            // 3. Verifikasi Akses Aplikasi
            $appIdSekarang = config('app.core_id');
            $punyaAkses = $user->roles()->wherePivot('app_id', $appIdSekarang)->exists();

            if (!$punyaAkses) {
                return back()->withErrors([
                    'login_id' => 'Akun Anda valid, tetapi tidak memiliki otoritas untuk mengakses aplikasi ini.',
                ]);
            }

            // 4. Eksekusi Login
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            return redirect()->intended('dashboard');
        }

        return back()->withErrors([
            'login_id' => 'Kredensial yang diberikan tidak cocok dengan data kami.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
