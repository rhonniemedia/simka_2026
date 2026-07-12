<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureHasAppAccess
{
    public function handle(Request $request, Closure $next)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $hasAccess = $user->hasAccessToApp(config('app.core_id'));

        if (! $hasAccess) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'login_id' => 'Anda tidak memiliki otoritas untuk mengakses aplikasi ini.',
            ]);
        }

        return $next($request);
    }
}
