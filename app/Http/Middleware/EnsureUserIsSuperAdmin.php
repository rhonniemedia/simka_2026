<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSuperAdmin
{
    /**
     * Berjalan SETELAH auth + app.access (grup admin). Mempersempit akses
     * khusus route yang hanya boleh dibuka superadmin, mis. /admin/users.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = Auth::user();

        $isSuperAdmin = $user && $user->roles()
            ->wherePivot('app_id', config('app.core_id'))
            ->where('user_roles.name', 'superadmin')
            ->exists();

        if ($isSuperAdmin) {
            return $next($request);
        }

        return response()->view('pages.auth.forbidden', [], 403);
    }
}
