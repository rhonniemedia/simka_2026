<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class SessionController extends Controller
{
    /**
     * Setiap request yang melewati middleware session otomatis
     * memperbarui masa berlaku sesi, jadi cukup mengembalikan OK.
     */
    public function keepAlive(): JsonResponse
    {
        return response()->json([
            'status'   => 'ok',
            'lifetime' => config('session.lifetime'),
        ]);
    }
}
