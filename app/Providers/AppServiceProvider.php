<?php

namespace App\Providers;

use App\Models\EducationHistory;
use App\Observers\EducationHistoryObserver;
use Illuminate\Database\Eloquent\Relations\Relation;
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

        Relation::morphMap([
            'staff_education_histories'       => \App\Models\EducationHistory::class,
            'staff_family_relations'          => \App\Models\FamilyRelation::class,
            'staff_asn_positions_histories'   => \App\Models\AsnPositionHistory::class,
            'staff_periodic_salary_histories' => \App\Models\PeriodicSalaryHistory::class,
            'staff_grade_histories'           => \App\Models\GradeHistory::class,
            'staff_status_histories'          => \App\Models\StaffStatusHistory::class,
        ]);
    }
}
