<?php

namespace App\Providers;

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
        // Map morph types for KeyLog holder relationship
        Relation::morphMap([
            'hr' => \App\Models\HrStaff::class,
            'perm_manual' => \App\Models\PermanentStaffManual::class,
            'temp' => \App\Models\TemporaryStaff::class,
        ]);
    }
}