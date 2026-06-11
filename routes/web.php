<?php

use App\Http\Controllers\AdminContainerController;
use App\Http\Controllers\AdminStudentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChangePasswordController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\StudentContainerPhotoController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentMoveTrackingController;
use App\Http\Controllers\StudentProfileController;
use App\Mail\ResetPasswordMail;
use App\Mail\OnboardingCompleteMail;
use App\Mail\PasswordChangedMail;
use App\Mail\StudentInvitationMail;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

if (app()->environment('local')) {
    Route::prefix('dev/email-preview')->group(function () {
        $previewUser = function (): User {
            return new User([
                'name' => 'Alex Carter',
                'email' => 'alex.carter@example.com',
            ]);
        };

        Route::get('/student-invitation', function () use ($previewUser) {
            return (new StudentInvitationMail($previewUser(), 'TempP@ss-9421'))->render();
        });

        Route::get('/password-changed-first', function () use ($previewUser) {
            return (new PasswordChangedMail($previewUser(), wasFirstReset: true))->render();
        });

        Route::get('/password-changed', function () use ($previewUser) {
            return (new PasswordChangedMail($previewUser(), wasFirstReset: false))->render();
        });

        Route::get('/onboarding-complete', function () use ($previewUser) {
            return (new OnboardingCompleteMail($previewUser()))->render();
        });

        Route::get('/reset-password', function () use ($previewUser) {
            return (new ResetPasswordMail(
                $previewUser(),
                url('/password/reset/sample-token?email=alex.carter@example.com'),
            ))->render();
        });
    });
}

Route::middleware('guest')->group(function () {
    Route::get('/password/forgot', [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/password/forgot', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');
    Route::get('/password/reset/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
    Route::post('/password/reset', [ResetPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.update');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

Route::prefix('student')->name('student.')->middleware([
    'auth',
    'account.active',
    'role:student',
    'password.changed',
    'onboarding.complete',
])->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');

    Route::get('/retail-packages', function () {
        return view('pages.portal.student.retail-packages', [
            'title' => 'Retail Packages',
            'portal' => 'student',
        ]);
    })->name('retail-packages');

    Route::get('/move-tracking', [StudentMoveTrackingController::class, 'index'])->name('move-tracking');
    Route::post('/move-tracking/containers/{container}/photos', [StudentContainerPhotoController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('move-tracking.photos.store');
    Route::delete('/move-tracking/containers/{container}/photos/{photo}', [StudentContainerPhotoController::class, 'destroy'])
        ->middleware('throttle:30,1')
        ->name('move-tracking.photos.destroy');

    Route::get('/add-ons', function () {
        return view('pages.portal.student.add-ons', [
            'title' => 'Add-Ons',
            'portal' => 'student',
        ]);
    })->name('add-ons');

    Route::get('/support', function () {
        return view('pages.portal.student.support', [
            'title' => 'Support',
            'portal' => 'student',
        ]);
    })->name('support');

    Route::get('/notifications', function () {
        return view('pages.portal.student.notifications', [
            'title' => 'Notifications',
            'portal' => 'student',
        ]);
    })->name('notifications');

    Route::get('/settings', function () {
        return view('pages.portal.student.settings', [
            'title' => 'Settings',
            'portal' => 'student',
        ]);
    })->name('settings');
});

Route::prefix('student')->name('student.')->middleware(['auth', 'account.active', 'role:student', 'password.changed'])->group(function () {
    Route::get('/profile', [StudentProfileController::class, 'show'])->name('profile');
    Route::post('/profile', [StudentProfileController::class, 'update'])->name('profile.update');

    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding');
    Route::post('/onboarding', [OnboardingController::class, 'submit'])->name('onboarding.submit');
});

Route::prefix('student')->name('student.')->middleware(['auth', 'account.active', 'role:student'])->group(function () {
    Route::get('/change-password', [ChangePasswordController::class, 'show'])->name('change-password');
    Route::post('/change-password', [ChangePasswordController::class, 'update'])
        ->middleware('throttle:10,1')
        ->name('change-password.submit');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', function () {
        return view('pages.portal.admin.dashboard', [
            'title' => 'Admin Dashboard',
            'pageHeading' => 'Dashboard Overview',
            'portal' => 'admin',
        ]);
    })->name('dashboard');

    Route::get('/customers', [AdminStudentController::class, 'index'])->name('customers');

    Route::get('/containers', [AdminContainerController::class, 'index'])->name('containers');
    Route::put('/containers/{container}', [AdminContainerController::class, 'update'])
        ->middleware('throttle:30,1')
        ->name('containers.update');

    Route::get('/retail-packages', function () {
        return view('pages.portal.admin.retail-packages', [
            'title' => 'Retail Package Management',
            'pageHeading' => 'Retail Packages',
            'portal' => 'admin',
        ]);
    })->name('retail-packages');

    Route::get('/deliveries', function () {
        return view('pages.portal.admin.deliveries', [
            'title' => 'Deliveries Management',
            'pageHeading' => 'Deliveries',
            'portal' => 'admin',
        ]);
    })->name('deliveries');

    Route::get('/add-ons', function () {
        return view('pages.portal.admin.add-ons', [
            'title' => 'Add-Ons Management',
            'pageHeading' => 'Add-Ons',
            'portal' => 'admin',
        ]);
    })->name('add-ons');

    Route::get('/communications', function () {
        return view('pages.portal.admin.communications', [
            'title' => 'Communications Center',
            'portal' => 'admin',
        ]);
    })->name('communications');

    Route::get('/reports', function () {
        return view('pages.portal.admin.reports', [
            'title' => 'Reports & Exports',
            'pageHeading' => 'Reports',
            'portal' => 'admin',
        ]);
    })->name('reports');

    Route::get('/notifications', function () {
        return view('pages.portal.admin.notifications', [
            'title' => 'Notifications',
            'pageHeading' => 'Notifications',
            'portal' => 'admin',
        ]);
    })->name('notifications');

    Route::get('/settings', function () {
        return view('pages.portal.admin.settings', [
            'title' => 'Admin Settings',
            'portal' => 'admin',
        ]);
    })->name('settings');

    Route::get('/profile', function () {
        return view('pages.portal.common.profile', [
            'title' => 'Admin Profile',
            'portal' => 'admin',
        ]);
    })->name('profile');

    Route::get('/change-password', [ChangePasswordController::class, 'show'])->name('change-password');
    Route::post('/change-password', [ChangePasswordController::class, 'update'])
        ->middleware('throttle:10,1')
        ->name('change-password.submit');
});
