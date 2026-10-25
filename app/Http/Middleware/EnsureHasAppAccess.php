<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasAppAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $appId = config('app.core_id');

        // Salah konfigurasi harus gagal dengan jelas (dan tercatat di log),
        // bukan menghasilkan TypeError karena hasAccessToApp() meminta string.
        if (blank($appId)) {
            throw new \RuntimeException('CORE_APP_ID belum diatur di .env (config app.core_id).');
        }

        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        $isActive = $user?->status === 'active';

        if ($isActive && $user->hasAdminAccessToApp((string) $appId)) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = $user && ! $isActive
            ? 'Akun Anda tidak aktif. Silakan hubungi administrator.'
            : 'Anda tidak memiliki otoritas untuk mengakses aplikasi ini.';

        // Request AJAX/fetch (mis. keep-alive idle timeout): jangan redirect
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 401);
        }

        // withErrors() langsung menyimpan pesan ke sesi (flash), jadi harus
        // dipanggil sebelum cabang HTMX agar pesan tampil di halaman login.
        $redirect = redirect()->route('login')->withErrors([
            'login_id' => $message,
        ]);

        // Request HTMX: redirect biasa akan membuat halaman login ditempel ke
        // area konten (dan pesan flash terpakai di sana). HX-Redirect memaksa
        // browser pindah halaman penuh sehingga pesan tampil di halaman login.
        if ($request->header('HX-Request')) {
            return response('', 401)->header('HX-Redirect', route('login'));
        }

        return $redirect;
    }
}
