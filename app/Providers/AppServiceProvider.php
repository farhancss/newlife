<?php

namespace App\Providers;

use App\Http\View\Composers\StudentProfileCompletionComposer;
use Illuminate\Support\Facades\View;
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
        $this->loadMigrationsFrom(base_path('new_migration'));

        View::composer(
            ['layouts.app-header', 'layouts.app', 'layouts.partials.student-sidebar'],
            StudentProfileCompletionComposer::class
        );
    }
}
