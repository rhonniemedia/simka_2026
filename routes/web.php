<?php

use App\Http\Controllers\Admin\Dashboard\DashboardController;
use App\Http\Controllers\Admin\Master\Reference\MasterController;
use App\Http\Controllers\Admin\Master\Reference\EmployeeDocumentController;
use App\Http\Controllers\Admin\Master\Reference\EmployeePositionController;
use App\Http\Controllers\Admin\Master\Reference\EmployeeEducationController;
use App\Http\Controllers\Admin\Master\Reference\EmployeeStatusController;
use App\Http\Controllers\Admin\Master\Reference\EmployeeGradeController;
use App\Http\Controllers\Admin\Master\Reference\EmployeeTypeController;
use App\Http\Controllers\Admin\Master\Reference\EmployeeStructureController;
use App\Http\Controllers\Admin\Master\Employee\DataController;
use App\Http\Controllers\Admin\User\ListController;
use App\Http\Controllers\Admin\User\ProfileController;
use App\Http\Controllers\Admin\Administration\DocumentController;
use App\Http\Controllers\Admin\Administration\EducationController;
use App\Http\Controllers\Admin\Administration\FamilyController;
use App\Http\Controllers\Admin\Career\PositionHistoryController;
use App\Http\Controllers\Admin\Career\SalariesHistoryController;
use App\Http\Controllers\Admin\Career\GradeHistoryController;
use App\Http\Controllers\Admin\Career\TransferController;
use App\Http\Controllers\Admin\Career\RetirementController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\SessionController;
use App\Http\Middleware\RememberIntendedUrl;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.home');
});

Route::middleware(['guest', RememberIntendedUrl::class])->group(function () {
    Route::get('/auth/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/auth/login', [LoginController::class, 'authenticate']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Dipanggil otomatis oleh idle-timeout.js untuk memperpanjang sesi
Route::post('/session/keep-alive', [SessionController::class, 'keepAlive'])
    ->middleware(['auth', 'app.access', 'throttle:30,1'])
    ->name('session.keep-alive');

Route::get('/admin/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'app.access'])
    ->name('admin.home');

// Seluruh route admin wajib login + lolos pengecekan akses aplikasi.
Route::prefix('admin')->name('admin.')->middleware(['auth', 'app.access'])->group(function () {

    // --- TAMBAHKAN BLOK MASTER INI ---
    Route::prefix('master')->name('master.')->group(function () {
        // Halaman Utama Master Data
        Route::get('/', [MasterController::class, 'index'])->name('index');

        // 1. Endpoint CRUD: Jenis Personel
        Route::get('/personnel-types', [EmployeeTypeController::class, 'index'])->name('personnel-types');
        Route::get('/personnel-types/create', [EmployeeTypeController::class, 'create'])->name('personnel-types.create');
        Route::post('/personnel-types', [EmployeeTypeController::class, 'store'])->name('personnel-types.store');
        Route::get('/personnel-types/{id}/edit', [EmployeeTypeController::class, 'edit'])->name('personnel-types.edit');
        Route::put('/personnel-types/{id}', [EmployeeTypeController::class, 'update'])->name('personnel-types.update');
        Route::delete('/personnel-types/{id}', [EmployeeTypeController::class, 'destroy'])->name('personnel-types.destroy');

        // 2. Endpoint CRUD: Status Kepegawaian (Ini yang sebelumnya kurang)
        Route::get('/employment-statuses', [EmployeeStatusController::class, 'index'])->name('employment-statuses');
        Route::get('/employment-statuses/create', [EmployeeStatusController::class, 'create'])->name('employment-statuses.create');
        Route::post('/employment-statuses', [EmployeeStatusController::class, 'store'])->name('employment-statuses.store');
        Route::get('/employment-statuses/{id}/edit', [EmployeeStatusController::class, 'edit'])->name('employment-statuses.edit');
        Route::put('/employment-statuses/{id}', [EmployeeStatusController::class, 'update'])->name('employment-statuses.update');
        Route::delete('/employment-statuses/{id}', [EmployeeStatusController::class, 'destroy'])->name('employment-statuses.destroy');

        // 3. Endpoint CRUD: Golongan Pangkat
        Route::get('/grades', [EmployeeGradeController::class, 'index'])->name('grades');
        Route::get('/grades/create', [EmployeeGradeController::class, 'create'])->name('grades.create');
        Route::post('/grades', [EmployeeGradeController::class, 'store'])->name('grades.store');
        Route::get('/grades/{id}/edit', [EmployeeGradeController::class, 'edit'])->name('grades.edit');
        Route::put('/grades/{id}', [EmployeeGradeController::class, 'update'])->name('grades.update');
        Route::delete('/grades/{id}', [EmployeeGradeController::class, 'destroy'])->name('grades.destroy');

        // 4. Endpoint CRUD: Tingkat Pendidikan
        Route::get('/education-levels', [EmployeeEducationController::class, 'index'])->name('education-levels');
        Route::get('/education-levels/create', [EmployeeEducationController::class, 'create'])->name('education-levels.create');
        Route::post('/education-levels', [EmployeeEducationController::class, 'store'])->name('education-levels.store');
        Route::get('/education-levels/{id}/edit', [EmployeeEducationController::class, 'edit'])->name('education-levels.edit');
        Route::put('/education-levels/{id}', [EmployeeEducationController::class, 'update'])->name('education-levels.update');
        Route::delete('/education-levels/{id}', [EmployeeEducationController::class, 'destroy'])->name('education-levels.destroy');

        // 5. Endpoint CRUD: Jabatan Organisasi
        Route::get('/positions', [EmployeeStructureController::class, 'index'])->name('positions');
        Route::get('/positions/create', [EmployeeStructureController::class, 'create'])->name('positions.create');
        Route::post('/positions', [EmployeeStructureController::class, 'store'])->name('positions.store');
        Route::get('/positions/{id}/edit', [EmployeeStructureController::class, 'edit'])->name('positions.edit');
        Route::put('/positions/{id}', [EmployeeStructureController::class, 'update'])->name('positions.update');
        Route::delete('/positions/{id}', [EmployeeStructureController::class, 'destroy'])->name('positions.destroy');

        // 6. Endpoint CRUD: Jabatan Kepegawaian (ASN)
        Route::get('/asn-positions', [EmployeePositionController::class, 'index'])->name('asn-positions');
        Route::get('/asn-positions/create', [EmployeePositionController::class, 'create'])->name('asn-positions.create');
        Route::post('/asn-positions', [EmployeePositionController::class, 'store'])->name('asn-positions.store');
        Route::get('/asn-positions/{id}/edit', [EmployeePositionController::class, 'edit'])->name('asn-positions.edit');
        Route::put('/asn-positions/{id}', [EmployeePositionController::class, 'update'])->name('asn-positions.update');
        Route::delete('/asn-positions/{id}', [EmployeePositionController::class, 'destroy'])->name('asn-positions.destroy');

        // 7. Endpoint CRUD: Kategori Dokumen
        // Penamaan disamakan dengan resource master lain (tanpa ".index" untuk daftar):
        // admin.master.document-categories, .create, .store, .edit, .update, .destroy
        Route::get('/document-categories', [EmployeeDocumentController::class, 'index'])->name('document-categories');
        Route::get('/document-categories/create', [EmployeeDocumentController::class, 'create'])->name('document-categories.create');
        Route::post('/document-categories', [EmployeeDocumentController::class, 'store'])->name('document-categories.store');
        Route::get('/document-categories/{id}/edit', [EmployeeDocumentController::class, 'edit'])->name('document-categories.edit');
        Route::put('/document-categories/{id}', [EmployeeDocumentController::class, 'update'])->name('document-categories.update');
        Route::delete('/document-categories/{id}', [EmployeeDocumentController::class, 'destroy'])->name('document-categories.destroy');
    });
    // ---------------------------------

    // Daftar Pengguna (akun, role, status, kata sandi)
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [ListController::class, 'index'])->name('index');
        Route::get('/create', [ListController::class, 'create'])->name('create');
        Route::post('/', [ListController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [ListController::class, 'edit'])->name('edit');
        Route::put('/{user}', [ListController::class, 'update'])->name('update');
        Route::delete('/{user}/access', [ListController::class, 'revokeAccess'])->name('revoke-access');

        Route::get('/{user}/edit-role', [ListController::class, 'editRole'])->name('edit-role');
        Route::put('/{user}/edit-role', [ListController::class, 'updateRole'])->name('edit-role.update');

        Route::get('/{user}/edit-password', [ListController::class, 'editPassword'])->name('edit-password');
        Route::put('/{user}/edit-password', [ListController::class, 'updatePassword'])->name('edit-password.update');
    });

    // Profil Saya (akun yang sedang login)
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('index');
        Route::post('/photo', [ProfileController::class, 'uploadPhoto'])->name('upload-photo');

        Route::get('/edit-data', [ProfileController::class, 'editData'])->name('edit-data');
        Route::put('/edit-data', [ProfileController::class, 'updateData'])->name('update-data');

        Route::get('/edit-password', [ProfileController::class, 'editPassword'])->name('edit-password');
        Route::put('/edit-password', [ProfileController::class, 'updatePassword'])->name('update-password');
    });

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
            Route::get('/', [EducationController::class, 'index'])->name('index');
            Route::get('/{id}', [EducationController::class, 'show'])->name('show');
            // Tambahan untuk modal Tambah & Edit Pendidikan
            Route::get('/{id}/create', [EducationController::class, 'create'])->name('create');
            Route::post('/{id}/store', [EducationController::class, 'store'])->name('store');
            Route::get('/{staff_id}/edit/{edu_id}', [EducationController::class, 'edit'])->name('edit');
            Route::put('/{staff_id}/update/{edu_id}', [EducationController::class, 'update'])->name('update');
            Route::delete('/{staff_id}/destroy/{edu_id}', [EducationController::class, 'destroyEducation'])->name('destroy');
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
            Route::get('/', [SalariesHistoryController::class, 'index'])->name('index');
            Route::get('/{id}', [SalariesHistoryController::class, 'show'])->name('show');

            // Rute Manajemen Data (Modal HTMX)
            Route::get('/{id}/create', [SalariesHistoryController::class, 'create'])->name('create');
            Route::post('/{id}/store', [SalariesHistoryController::class, 'store'])->name('store');
            Route::get('/{staff_id}/edit/{history_id}', [SalariesHistoryController::class, 'edit'])->name('edit');
            Route::put('/{staff_id}/update/{history_id}', [SalariesHistoryController::class, 'update'])->name('update');
            Route::delete('/{staff_id}/destroy/{history_id}', [SalariesHistoryController::class, 'destroy'])->name('destroy');
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

        // 9. Mutasi (Perubahan Status Pegawai: pindah, mengundurkan diri,
        //    meninggal, diberhentikan; sekaligus reaktivasi. Pensiun tidak
        //    diubah dari sini, tetap lewat menu Pensiun di atas.)
        Route::prefix('mutation')->name('mutation.')->group(function () {
            Route::get('/', [TransferController::class, 'index'])->name('index');
            Route::get('/create', [TransferController::class, 'create'])->name('create');
            Route::get('/create-reaktivasi', [TransferController::class, 'createReactivation'])->name('create-reaktivasi');
            Route::post('/store', [TransferController::class, 'store'])->name('store');
        });
    });
});
