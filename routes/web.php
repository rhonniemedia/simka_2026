<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'authenticate']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

Route::get('/dashboard', function () {
    $username = auth()->user()->username;
    $logoutRoute = route('logout');
    $csrf = csrf_field();

    return "
        <div style='font-family: sans-serif; padding: 3rem;'>
            <h1 style='font-size: 1.5rem; font-weight: bold; margin-bottom: 1rem; color: #1f2937;'>
                Selamat datang di SIMKA, {$username}!
            </h1>
            
            <form method='POST' action='{$logoutRoute}'>
                {$csrf}
                <button type='submit' style='background-color: #ef4444; color: white; font-weight: 600; padding: 0.6rem 1.2rem; border-radius: 0.5rem; border: none; cursor: pointer;'>
                    Logout
                </button>
            </form>
        </div>
    ";
})->middleware(['auth', 'app.access'])->name('dashboard');
