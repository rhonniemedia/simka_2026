<?php

use App\Http\Controllers\Admin\Documents\DocumentCategoryController;
use App\Http\Controllers\Admin\Master\AsnPositionController;
use App\Http\Controllers\Admin\Master\EducationLevelController;
use App\Http\Controllers\Admin\Master\EmploymentStatusController;
use App\Http\Controllers\Admin\Master\GradeController;
use App\Http\Controllers\Admin\Master\MasterController;
use App\Http\Controllers\Admin\Master\PersonnelTypeController;
use App\Http\Controllers\Admin\Master\PositionController;
use App\Http\Controllers\Admin\Payroll\PeriodicSalaryHistoryController;
use App\Http\Controllers\Admin\Personnel\DataController;
use App\Http\Controllers\Admin\Personnel\DocumentController;
use App\Http\Controllers\Admin\Personnel\EducationHistoryController;
use App\Http\Controllers\Admin\Personnel\FamilyController;
use App\Http\Controllers\Admin\Personnel\GradeHistoryController;
use App\Http\Controllers\Admin\Personnel\PositionHistoryController;
use App\Http\Controllers\Admin\Personnel\RetirementController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Auth;
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
    $username = Auth::user()->username;
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

    // --- TAMBAHKAN BLOK MASTER INI ---
    Route::prefix('master')->name('master.')->group(function () {
        // Halaman Utama Master Data
        Route::get('/', [MasterController::class, 'index'])->name('index');

        // 1. Endpoint CRUD: Jenis Personel
        Route::get('/personnel-types', [PersonnelTypeController::class, 'index'])->name('personnel-types');
        Route::get('/personnel-types/create', [PersonnelTypeController::class, 'create'])->name('personnel-types.create');
        Route::post('/personnel-types', [PersonnelTypeController::class, 'store'])->name('personnel-types.store');
        Route::get('/personnel-types/{id}/edit', [PersonnelTypeController::class, 'edit'])->name('personnel-types.edit');
        Route::put('/personnel-types/{id}', [PersonnelTypeController::class, 'update'])->name('personnel-types.update');
        Route::delete('/personnel-types/{id}', [PersonnelTypeController::class, 'destroy'])->name('personnel-types.destroy');

        // 2. Endpoint CRUD: Status Kepegawaian (Ini yang sebelumnya kurang)
        Route::get('/employment-statuses', [EmploymentStatusController::class, 'index'])->name('employment-statuses');
        Route::get('/employment-statuses/create', [EmploymentStatusController::class, 'create'])->name('employment-statuses.create');
        Route::post('/employment-statuses', [EmploymentStatusController::class, 'store'])->name('employment-statuses.store');
        Route::get('/employment-statuses/{id}/edit', [EmploymentStatusController::class, 'edit'])->name('employment-statuses.edit');
        Route::put('/employment-statuses/{id}', [EmploymentStatusController::class, 'update'])->name('employment-statuses.update');
        Route::delete('/employment-statuses/{id}', [EmploymentStatusController::class, 'destroy'])->name('employment-statuses.destroy');

        // 3. Endpoint CRUD: Golongan Pangkat
        Route::get('/grades', [GradeController::class, 'index'])->name('grades');
        Route::get('/grades/create', [GradeController::class, 'create'])->name('grades.create');
        Route::post('/grades', [GradeController::class, 'store'])->name('grades.store');
        Route::get('/grades/{id}/edit', [GradeController::class, 'edit'])->name('grades.edit');
        Route::put('/grades/{id}', [GradeController::class, 'update'])->name('grades.update');
        Route::delete('/grades/{id}', [GradeController::class, 'destroy'])->name('grades.destroy');

        // 4. Endpoint CRUD: Tingkat Pendidikan
        Route::get('/education-levels', [EducationLevelController::class, 'index'])->name('education-levels');
        Route::get('/education-levels/create', [EducationLevelController::class, 'create'])->name('education-levels.create');
        Route::post('/education-levels', [EducationLevelController::class, 'store'])->name('education-levels.store');
        Route::get('/education-levels/{id}/edit', [EducationLevelController::class, 'edit'])->name('education-levels.edit');
        Route::put('/education-levels/{id}', [EducationLevelController::class, 'update'])->name('education-levels.update');
        Route::delete('/education-levels/{id}', [EducationLevelController::class, 'destroy'])->name('education-levels.destroy');

        // 5. Endpoint CRUD: Jabatan Organisasi
        Route::get('/positions', [PositionController::class, 'index'])->name('positions');
        Route::get('/positions/create', [PositionController::class, 'create'])->name('positions.create');
        Route::post('/positions', [PositionController::class, 'store'])->name('positions.store');
        Route::get('/positions/{id}/edit', [PositionController::class, 'edit'])->name('positions.edit');
        Route::put('/positions/{id}', [PositionController::class, 'update'])->name('positions.update');
        Route::delete('/positions/{id}', [PositionController::class, 'destroy'])->name('positions.destroy');

        // 6. Endpoint CRUD: Jabatan Kepegawaian (ASN)
        Route::get('/asn-positions', [AsnPositionController::class, 'index'])->name('asn-positions');
        Route::get('/asn-positions/create', [AsnPositionController::class, 'create'])->name('asn-positions.create');
        Route::post('/asn-positions', [AsnPositionController::class, 'store'])->name('asn-positions.store');
        Route::get('/asn-positions/{id}/edit', [AsnPositionController::class, 'edit'])->name('asn-positions.edit');
        Route::put('/asn-positions/{id}', [AsnPositionController::class, 'update'])->name('asn-positions.update');
        Route::delete('/asn-positions/{id}', [AsnPositionController::class, 'destroy'])->name('asn-positions.destroy');

        // 7. Endpoint CRUD: Kategori Dokumen
        // Penamaan disamakan dengan resource master lain (tanpa ".index" untuk daftar):
        // admin.master.document-categories, .create, .store, .edit, .update, .destroy
        Route::get('/document-categories', [DocumentCategoryController::class, 'index'])->name('document-categories');
        Route::get('/document-categories/create', [DocumentCategoryController::class, 'create'])->name('document-categories.create');
        Route::post('/document-categories', [DocumentCategoryController::class, 'store'])->name('document-categories.store');
        Route::get('/document-categories/{id}/edit', [DocumentCategoryController::class, 'edit'])->name('document-categories.edit');
        Route::put('/document-categories/{id}', [DocumentCategoryController::class, 'update'])->name('document-categories.update');
        Route::delete('/document-categories/{id}', [DocumentCategoryController::class, 'destroy'])->name('document-categories.destroy');
    });
    // ---------------------------------

    // Grup Utama: Pegawai (Kepegawaian)
    Route::prefix('personnel')->name('personnel.')->group(function () {

        // 1. Data Pegawai
        Route::prefix('data')->name('data.')->group(function () {
            Route::get('/', [DataController::class, 'index'])->name('index');
            Route::get('/create', [DataController::class, 'create'])->name('create');
            Route::get('/{id}/edit', [DataController::class, 'edit'])->name('edit');
            Route::delete('/{id}', [DataController::class, 'destroy'])->name('destroy');

            // Simpan per step (pengganti store & update yang lama).
            // - store-step  : step 1 pada data baru -> membuat draft.
            // - update-step : step 1..4 pada data yang sudah ada (draft maupun edit).
            //                 Step terakhir mengubah draft menjadi data lengkap.
            // - discard-draft : membuang draft yang belum selesai.
            Route::post('/step', [DataController::class, 'storeStep'])->name('store-step');
            Route::put('/{id}/step', [DataController::class, 'updateStep'])->name('update-step');
            Route::delete('/{id}/draft', [DataController::class, 'discardDraft'])->name('discard-draft');

            // Rute modal (dipanggil via HTMX dari menu Aksi di tabel).
            // Method-nya sudah ada di DataController tapi belum pernah didaftarkan sebagai rute.
            Route::get('/{id}/detail-personal', [DataController::class, 'detailPersonal'])->name('detail-personal');
            Route::get('/{id}/detail-employment', [DataController::class, 'detailEmployment'])->name('detail-employment');
            Route::get('/{id}/edit-personal', [DataController::class, 'editPersonal'])->name('edit-personal');
            Route::get('/{id}/edit-photo', [DataController::class, 'editPhoto'])->name('edit-photo');
            Route::put('/{id}/photo', [DataController::class, 'updatePhoto'])->name('update-photo');
        });

        // 2. Dokumen Pegawai
        Route::prefix('documents')->name('documents.')->group(function () {
            Route::get('/', [DocumentController::class, 'index'])->name('index');
            Route::get('/create', [DocumentController::class, 'create'])->name('create');
            Route::post('/', [DocumentController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [DocumentController::class, 'edit'])->name('edit');
            Route::put('/{id}', [DocumentController::class, 'update'])->name('update');
            Route::delete('/{id}', [DocumentController::class, 'destroy'])->name('destroy');

            // Akses file - HANYA lewat controller ini (di balik middleware auth
            // grup admin), tidak pernah lewat URL statis/disk publik.
            Route::get('/{id}/preview', [DocumentController::class, 'preview'])->name('preview');
            Route::get('/{id}/download', [DocumentController::class, 'download'])->name('download');
        });

        // 3. Kepangkatan
        Route::prefix('promotions')->name('promotions.')->group(function () {
            Route::get('/', [GradeHistoryController::class, 'index'])->name('index');
            Route::get('/{id}', [GradeHistoryController::class, 'show'])->name('show');

            // Rute Manajemen Data (Modal HTMX)
            Route::get('/{id}/create', [GradeHistoryController::class, 'create'])->name('create');
            Route::post('/{id}/store', [GradeHistoryController::class, 'store'])->name('store');
            Route::get('/{staff_id}/edit/{history_id}', [GradeHistoryController::class, 'edit'])->name('edit');
            Route::put('/{staff_id}/update/{history_id}', [GradeHistoryController::class, 'update'])->name('update');
            Route::delete('/{staff_id}/destroy/{history_id}', [GradeHistoryController::class, 'destroy'])->name('destroy');
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
            Route::delete('/{staff_id}/destroy/{edu_id}', [EducationHistoryController::class, 'destroyEducation'])->name('destroy');
        });


        // 5. Keluarga
        Route::prefix('family')->name('family.')->group(function () {
            Route::get('/', [FamilyController::class, 'index'])->name('index');
            Route::get('/{id}', [FamilyController::class, 'show'])->name('show');

            // Rute Manajemen Data Keluarga (Modal HTMX)
            Route::get('/{id}/create', [FamilyController::class, 'create'])->name('create');
            Route::post('/{id}/store', [FamilyController::class, 'store'])->name('store');
            Route::get('/{staff_id}/edit/{family_id}', [FamilyController::class, 'edit'])->name('edit');
            Route::put('/{staff_id}/update/{family_id}', [FamilyController::class, 'update'])->name('update');
            Route::delete('/{staff_id}/destroy/{family_id}', [FamilyController::class, 'destroy'])->name('destroy');

            Route::post('/{staff}/check-nik', [FamilyController::class, 'checkNik'])
                ->name('check-nik');
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
            Route::get('/', [RetirementController::class, 'index'])->name('index');

            // Proses pensiun (Modal HTMX)
            Route::get('/{id}/process', [RetirementController::class, 'process'])->name('process');
            Route::post('/{id}/process', [RetirementController::class, 'store'])->name('process.store');
        });

        // 8. Jabatan ASN (Riwayat Jabatan Fungsional/Pelaksana)
        Route::prefix('positions')->name('positions.')->group(function () {
            Route::get('/', [PositionHistoryController::class, 'index'])->name('index');
            Route::get('/{id}', [PositionHistoryController::class, 'show'])->name('show');

            // Rute Manajemen Data (Modal HTMX)
            Route::get('/{id}/create', [PositionHistoryController::class, 'create'])->name('create');
            Route::post('/{id}/store', [PositionHistoryController::class, 'store'])->name('store');
            Route::get('/{staff_id}/edit/{history_id}', [PositionHistoryController::class, 'edit'])->name('edit');
            Route::put('/{staff_id}/update/{history_id}', [PositionHistoryController::class, 'update'])->name('update');
            Route::delete('/{staff_id}/destroy/{history_id}', [PositionHistoryController::class, 'destroy'])->name('destroy');
        });
    });
});
