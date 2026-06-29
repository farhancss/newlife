<?php

use Illuminate\Support\Facades\Mail;

it('shows the reset password form', function () {
    $this->get(route('password.reset', ['token' => 'sometoken', 'email' => 'user@example.com']))
        ->assertOk();
});

it('redirects the reset success page to login without a success flag', function () {
    $this->get(route('password.reset.success'))->assertRedirect(route('login'));
});

it('shows the reset success page with a success flag', function () {
    $this->withSession(['password_reset_success' => true])
        ->get(route('password.reset.success'))
        ->assertOk();
});

it('shows the change password page for students and admins', function () {
    [$student] = completeStudent();
    $this->actingAs($student)->get(route('student.change-password'))->assertOk();

    $this->actingAs(makeAdmin())->get(route('admin.change-password'))->assertOk();
});

it('lets an admin change their password', function () {
    Mail::fake();
    $admin = makeAdmin();

    $this->actingAs($admin)
        ->post(route('admin.change-password.submit'), [
            'current_password' => 'password',
            'password' => 'BrandNewPass123!',
            'password_confirmation' => 'BrandNewPass123!',
        ])
        ->assertRedirect(route('admin.dashboard'))
        ->assertSessionHas('status');
});

it('rejects a student password change with the wrong current password', function () {
    [$student] = completeStudent();

    $this->actingAs($student)
        ->post(route('student.change-password.submit'), [
            'current_password' => 'WrongPass000!',
            'password' => 'BrandNewPass123!',
            'password_confirmation' => 'BrandNewPass123!',
        ])
        ->assertSessionHasErrors('current_password');
});

it('lets a complete student change their password and land on the dashboard', function () {
    Mail::fake();
    [$student] = completeStudent();

    $this->actingAs($student)
        ->post(route('student.change-password.submit'), [
            'current_password' => 'SecurePass123!',
            'password' => 'BrandNewPass123!',
            'password_confirmation' => 'BrandNewPass123!',
        ])
        ->assertRedirect(route('student.dashboard'));
});
