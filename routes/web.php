<?php

use App\Http\Controllers\Admin\Administration\DocumentController;
use App\Http\Controllers\Admin\Administration\EducationController;
use App\Http\Controllers\Admin\Administration\FamilyController;
use App\Http\Controllers\Admin\Career\GradeHistoryController;
use App\Http\Controllers\Admin\Career\PositionHistoryController;
use App\Http\Controllers\Admin\Career\RetirementController;
use App\Http\Controllers\Admin\Career\SalariesHistoryController;
use App\Http\Controllers\Admin\Career\TransferController;
use App\Http\Controllers\Admin\Dashboard\DashboardController;
use App\Http\Controllers\Admin\Master\Employee\DataController;
use App\Http\Controllers\Admin\Master\Reference\EmployeeDocumentController;
use App\Http\Controllers\Admin\Master\Reference\EmployeeEducationController;
use App\Http\Controllers\Admin\Master\Reference\EmployeeGradeController;
use App\Http\Controllers\Admin\Master\Reference\EmployeePositionController;
use App\Http\Controllers\Admin\Master\Reference\EmployeeStatusController;
use App\Http\Controllers\Admin\Master\Reference\EmployeeStructureController;
use App\Http\Controllers\Admin\Master\Reference\EmployeeTypeController;
use App\Http\Controllers\Admin\Master\Reference\MasterController;
use App\Http\Controllers\Admin\User\ListController;
use App\Http\Controllers\Admin\User\ProfileController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\SessionController;
use App\Http\Middleware\RememberIntendedUrl;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('admin.home'));

Route::middleware(['guest', RememberIntendedUrl::class])->group(function () {
    Route::get('/auth/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/auth/login', [LoginController::class, 'authenticate']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Dipanggil otomatis oleh idle-timeout.js untuk memperpanjang sesi
Route::post('/session/keep-alive', [SessionController::class, 'keepAlive'])
    ->middleware(['auth', 'app.access', 'throttle:30,1'])
    ->name('session.keep-alive');

/*
|--------------------------------------------------------------------------
| Helper pendaftar route berpola (hanya dipakai di berkas ini)
|--------------------------------------------------------------------------
*/

// CRUD master: nama route daftar = URI-nya (tanpa ".index"), mis. admin.master.grades
$masterCrud = function (string $uri, string $controller) {
    Route::get("/{$uri}", [$controller, 'index'])->name($uri);
    Route::get("/{$uri}/create", [$controller, 'create'])->name("{$uri}.create");
    Route::post("/{$uri}", [$controller, 'store'])->name("{$uri}.store");
    Route::get("/{$uri}/{id}/edit", [$controller, 'edit'])->name("{$uri}.edit");
    Route::put("/{$uri}/{id}", [$controller, 'update'])->name("{$uri}.update");
    Route::delete("/{$uri}/{id}", [$controller, 'destroy'])->name("{$uri}.destroy");
};

// Riwayat per pegawai (daftar, detail pegawai, dan modal HTMX tambah/ubah/hapus).
// $key = nama parameter id baris riwayat; $destroy = nama method hapus di controller.
$staffHistory = function (string $controller, string $key = 'history_id', string $destroy = 'destroy') {
    Route::get('/', [$controller, 'index'])->name('index');
    Route::get('/{id}', [$controller, 'show'])->name('show');
    Route::get('/{id}/create', [$controller, 'create'])->name('create');
    Route::post('/{id}/store', [$controller, 'store'])->name('store');
    Route::get('/{staff_id}/edit/{' . $key . '}', [$controller, 'edit'])->name('edit');
    Route::put('/{staff_id}/update/{' . $key . '}', [$controller, 'update'])->name('update');
    Route::delete('/{staff_id}/destroy/{' . $key . '}', [$controller, $destroy])->name('destroy');
};

// Seluruh route admin wajib login + lolos pengecekan akses aplikasi.
Route::prefix('admin')->name('admin.')->middleware(['auth', 'app.access'])->group(function () use ($masterCrud, $staffHistory) {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('home');

    // Master Data
    Route::prefix('master')->name('master.')->group(function () use ($masterCrud) {
        Route::get('/', [MasterController::class, 'index'])->name('index');

        $masters = [
            'personnel-types'     => EmployeeTypeController::class,      // Jenis Personel
            'employment-statuses' => EmployeeStatusController::class,    // Status Kepegawaian
            'grades'              => EmployeeGradeController::class,     // Golongan Pangkat
            'education-levels'    => EmployeeEducationController::class, // Tingkat Pendidikan
            'positions'           => EmployeeStructureController::class, // Jabatan Organisasi
            'asn-positions'       => EmployeePositionController::class,  // Jabatan Kepegawaian (ASN)
            'document-categories' => EmployeeDocumentController::class,  // Kategori Dokumen
        ];

        foreach ($masters as $uri => $controller) {
            $masterCrud($uri, $controller);
        }
    });

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

    // Pegawai (Kepegawaian)
    Route::prefix('personnel')->name('personnel.')->group(function () use ($staffHistory) {

        // 1. Data Pegawai
        Route::prefix('data')->name('data.')->group(function () {
            Route::get('/', [DataController::class, 'index'])->name('index');
            Route::get('/create', [DataController::class, 'create'])->name('create');
            Route::get('/{id}/edit', [DataController::class, 'edit'])->name('edit');
            Route::delete('/{id}', [DataController::class, 'destroy'])->name('destroy');

            // Simpan per step (pengganti store & update yang lama).
            // - store-step    : step 1 pada data baru -> membuat draft.
            // - update-step   : step 1..4 pada data yang sudah ada (draft maupun edit).
            //                   Step terakhir mengubah draft menjadi data lengkap.
            // - discard-draft : membuang draft yang belum selesai.
            Route::post('/step', [DataController::class, 'storeStep'])->name('store-step');
            Route::put('/{id}/step', [DataController::class, 'updateStep'])->name('update-step');
            Route::delete('/{id}/draft', [DataController::class, 'discardDraft'])->name('discard-draft');

            // Rute modal (dipanggil via HTMX dari menu Aksi di tabel)
            Route::get('/{id}/detail', [DataController::class, 'detail'])->name('detail');
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

            // Opsi data riwayat yang bisa ditautkan ke dokumen (dipanggil via fetch dari form)
            Route::get('/linkable', [DocumentController::class, 'linkable'])->name('linkable');

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
        Route::prefix('promotions')->name('promotions.')
            ->group(fn() => $staffHistory(GradeHistoryController::class));

        // 4. Pendidikan
        Route::prefix('education')->name('education.')
            ->group(fn() => $staffHistory(EducationController::class, 'edu_id', 'destroyEducation'));

        // 5. Keluarga
        Route::prefix('family')->name('family.')->group(function () use ($staffHistory) {
            $staffHistory(FamilyController::class, 'family_id');

            Route::post('/{staff}/check-nik', [FamilyController::class, 'checkNik'])->name('check-nik');
        });

        // 6. Berkala (Kenaikan Gaji Berkala)
        Route::prefix('periodic-salary')->name('periodic-salary.')
            ->group(fn() => $staffHistory(SalariesHistoryController::class));

        // 7. Pensiun
        Route::prefix('retirement')->name('retirement.')->group(function () {
            Route::get('/', [RetirementController::class, 'index'])->name('index');

            // Proses pensiun (Modal HTMX)
            Route::get('/{id}/process', [RetirementController::class, 'process'])->name('process');
            Route::post('/{id}/process', [RetirementController::class, 'store'])->name('process.store');

            // Edit data SK pensiun (Modal HTMX)
            Route::get('/{id}/edit', [RetirementController::class, 'edit'])->name('edit');
            Route::put('/{id}', [RetirementController::class, 'update'])->name('update');
        });

        // 8. Jabatan ASN (Riwayat Jabatan Fungsional/Pelaksana)
        Route::prefix('positions')->name('positions.')
            ->group(fn() => $staffHistory(PositionHistoryController::class));

        // 9. Mutasi (Perubahan Status Pegawai: pindah, mengundurkan diri,
        //    meninggal, diberhentikan; sekaligus reaktivasi. Pensiun tidak
        //    diubah dari sini, tetap lewat menu Pensiun di atas.)
        Route::prefix('mutation')->name('mutation.')->group(function () {
            Route::get('/', [TransferController::class, 'index'])->name('index');
            Route::get('/create', [TransferController::class, 'create'])->name('create');
            Route::get('/create-reaktivasi', [TransferController::class, 'createReactivation'])->name('create-reaktivasi');
            Route::post('/store', [TransferController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [TransferController::class, 'edit'])->name('edit');
            Route::put('/{id}', [TransferController::class, 'update'])->name('update');
        });
    });
});
