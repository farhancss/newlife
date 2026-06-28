<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Http\View\Composers\StudentProfileCompletionComposer;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
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
        View::composer(
            ['layouts.app-header', 'layouts.app', 'layouts.partials.student-sidebar'],
            StudentProfileCompletionComposer::class
        );

        // Restrict the Log Viewer UI (Squarespace webhook/API logs live here) to
        // admins only, including outside the local environment.
        Gate::define('viewLogViewer', static function (?User $user): bool {
            return $user !== null && $user->role === UserRole::ADMIN;
        });
    }
}
