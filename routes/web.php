<?php

use App\Enums\UserRole;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::redirect('/', '/login');

Route::get('/login', function () {
    return view('pages.portal.login', [
        'title' => 'Login',
    ]);
})->name('login');

Route::post('/login', function () {
    $credentials = request()->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    if (!Auth::attempt($credentials)) {
        return redirect()
            ->route('login')
            ->withInput(['email' => $credentials['email']])
            ->withErrors([
                'login' => 'The provided credentials are incorrect.',
            ]);
    }

    request()->session()->regenerate();

    $user = Auth::user();

    return $user->role === UserRole::ADMIN
        ? redirect()->route('admin.dashboard')
        : redirect()->route('student.dashboard');
})->name('login.submit');

Route::get('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->name('logout');

Route::prefix('student')->name('student.')->middleware(['auth', 'role:student'])->group(function () {
    Route::get('/dashboard', function () {
        return view('pages.portal.student.dashboard', [
            'title' => 'Student Dashboard',
            'portal' => 'student',
            'pageHeading' => 'Dashboard',
        ]);
    })->name('dashboard');

    Route::get('/retail-packages', function () {
        return view('pages.portal.student.retail-packages', [
            'title' => 'Retail Packages',
            'portal' => 'student',
        ]);
    })->name('retail-packages');

    Route::get('/profile', function () {
        return view('pages.portal.student.profile', [
            'title' => 'Student Profile',
            'portal' => 'student',
        ]);
    })->name('profile');

    Route::get('/move-tracking', function () {
        return view('pages.portal.student.move-tracking', [
            'title' => 'Move Tracking',
            'portal' => 'student',
        ]);
    })->name('move-tracking');

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

    Route::get('/change-password', function () {
        return view('pages.portal.common.change-password', [
            'title' => 'Change Password',
            'portal' => 'student',
        ]);
    })->name('change-password');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', function () {
        return view('pages.portal.admin.dashboard', [
            'title' => 'Admin Dashboard',
            'pageHeading' => 'Dashboard Overview',
            'portal' => 'admin',
        ]);
    })->name('dashboard');

    Route::get('/customers', function () {
        return view('pages.portal.admin.customers', [
            'title' => 'Student Management',
            'pageHeading' => 'Students',
            'portal' => 'admin',
        ]);
    })->name('customers');

    Route::get('/containers', function () {
        return view('pages.portal.admin.containers', [
            'title' => 'Container Management',
            'pageHeading' => 'Containers',
            'portal' => 'admin',
        ]);
    })->name('containers');

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

    Route::get('/change-password', function () {
        return view('pages.portal.common.change-password', [
            'title' => 'Change Password',
            'portal' => 'admin',
        ]);
    })->name('change-password');
});
