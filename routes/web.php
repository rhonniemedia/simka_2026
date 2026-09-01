<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\Personnel\DataController;
use App\Http\Controllers\Admin\Personnel\DocumentController;
use App\Http\Controllers\Admin\Personnel\EducationHistoryController;
use App\Http\Controllers\Admin\Personnel\FamilyController;
use App\Http\Controllers\Admin\Personnel\GradeHistoryController;
use App\Http\Controllers\Admin\Payroll\PeriodicSalaryHistoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('pages.home.index');
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

Route::prefix('admin')->name('admin.')->group(function () {

    // Grup Utama: Pegawai (Kepegawaian)
    Route::prefix('personnel')->name('personnel.')->group(function () {

        // 1. Data Pegawai
        Route::prefix('data')->name('data.')->group(function () {
            Route::get('/', [DataController::class, 'index'])->name('index');
            Route::get('/create', [DataController::class, 'create'])->name('create');
            Route::post('/', [DataController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [DataController::class, 'edit'])->name('edit');
            Route::put('/{id}', [DataController::class, 'update'])->name('update');
            Route::delete('/{id}', [DataController::class, 'destroy'])->name('destroy');

            // Rute modal (dipanggil via HTMX dari menu Aksi di tabel).
            // Method-nya sudah ada di DataController tapi belum pernah didaftarkan sebagai rute.
            Route::get('/{id}/detail-personal', [DataController::class, 'detailPersonal'])->name('detail-personal');
            Route::get('/{id}/detail-employment', [DataController::class, 'detailEmployment'])->name('detail-employment');
            Route::get('/{id}/edit-personal', [DataController::class, 'editPersonal'])->name('edit-personal');
        });

        // 2. Dokumen Pegawai
        Route::prefix('documents')->name('documents.')->group(function () {
            Route::get('/', [DocumentController::class, 'index'])->name('index');
            // Tambahkan rute CRUD dokumen di sini nantinya
        });

        // 3. Kepangkatan
        Route::prefix('promotions')->name('promotions.')->group(function () {
            Route::get('/', [GradeHistoryController::class, 'index'])->name('index');
            Route::get('/{id}', [GradeHistoryController::class, 'show'])->name('show'); // Tambahkan baris ini
        });

        // 4. Pendidikan
        Route::prefix('education')->name('education.')->group(function () {
            Route::get('/', [EducationHistoryController::class, 'index'])->name('index');
            Route::get('/{id}', [EducationHistoryController::class, 'show'])->name('show');
            // Tambahan untuk modal Tambah & Edit Pendidikan
            Route::get('/{id}/create', [EducationHistoryController::class, 'create'])->name('create');
            Route::post('/{id}/store', [EducationHistoryController::class, 'store'])->name('store');
            Route::get('/{staff_id}/edit/{edu_id}', [EducationHistoryController::class, 'edit'])->name('edit');
            Route::put('/{staff_id}/update/{edu_id}', [EducationHistoryController::class, 'update'])->name('update');
        });


        // 5. Keluarga
        Route::prefix('family')->name('family.')->group(function () {
            Route::get('/', [FamilyController::class, 'index'])->name('index');
            Route::get('/{id}', [FamilyController::class, 'show'])->name('show'); // Tambahkan rute ini
        });

        // 6. Berkala (Kenaikan Gaji Berkala)
        Route::prefix('periodic-salary')->name('periodic-salary.')->group(function () {
            Route::get('/', [PeriodicSalaryHistoryController::class, 'index'])->name('index');
            Route::get('/{id}', [PeriodicSalaryHistoryController::class, 'show'])->name('show');

            // Rute Manajemen Data (Modal HTMX)
            Route::get('/{id}/create', [PeriodicSalaryHistoryController::class, 'create'])->name('create');
            Route::post('/{id}/store', [PeriodicSalaryHistoryController::class, 'store'])->name('store');
            Route::get('/{staff_id}/edit/{history_id}', [PeriodicSalaryHistoryController::class, 'edit'])->name('edit');
            Route::put('/{staff_id}/update/{history_id}', [PeriodicSalaryHistoryController::class, 'update'])->name('update');
            Route::delete('/{staff_id}/destroy/{history_id}', [PeriodicSalaryHistoryController::class, 'destroy'])->name('destroy');
        });

        // 7. Pensiun
        Route::prefix('retirement')->name('retirement.')->group(function () {
            Route::get('/')->name('index');
        });
    });
});
