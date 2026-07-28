<?php

namespace App\Providers;

use App\Models\EducationHistory;
use App\Observers\EducationHistoryObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Daftarkan Observer untuk Riwayat Pendidikan
        EducationHistory::observe(EducationHistoryObserver::class);
    }
}
