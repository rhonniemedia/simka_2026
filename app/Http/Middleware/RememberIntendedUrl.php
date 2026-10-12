<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Menyimpan halaman terakhir user (dikirim idle-timeout.js lewat ?redirect=...)
 * sebagai "intended URL", sehingga setelah login user kembali ke halaman itu.
 */
class RememberIntendedUrl
{
    private const MAX_LENGTH = 2000;

    /** Path yang tidak boleh dijadikan tujuan setelah login. */
    private const BLOCKED_PREFIXES = ['/auth', '/logout', '/session'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET')) {
            $target = $request->query('redirect');

            if (is_string($target) && $this->isSafe($target)) {
                $request->session()->put(
                    'url.intended',
                    $request->getSchemeAndHttpHost() . $target
                );
            }
        }

        return $next($request);
    }

    private function isSafe(string $target): bool
    {
        // Hanya path relatif satu slash: tolak "//host", "/\host", URL absolut,
        // dan karakter kontrol (mencegah open redirect & header injection).
        if (
            $target === ''
            || strlen($target) > self::MAX_LENGTH
            || $target[0] !== '/'
            || str_starts_with($target, '//')
            || str_contains($target, '\\')
            || preg_match('/[\x00-\x1F\x7F]/', $target)
        ) {
            return false;
        }

        $path = parse_url($target, PHP_URL_PATH);
        if (! is_string($path)) {
            return false;
        }

        foreach (self::BLOCKED_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return false;
            }
        }

        // Hanya terima URL yang benar-benar ada sebagai route GET
        try {
            app('router')->getRoutes()->match(Request::create($target, 'GET'));
        } catch (HttpException) {
            return false;
        }

        return true;
    }
}
