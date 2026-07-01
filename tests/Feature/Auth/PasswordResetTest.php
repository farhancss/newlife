<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

test('forgot password page is accessible to guests', function () {
    $this->get(route('password.request'))->assertOk();
});

test('forgot password sends reset mail for existing user', function () {
    Mail::fake();

    $user = User::factory()->create([
        'email' => 'resetme@example.com',
        'role' => UserRole::STUDENT,
        'status' => UserStatus::ACTIVE,
    ]);

    $this->post(route('password.email'), ['email' => 'resetme@example.com'])
        ->assertRedirect()
        ->assertSessionHas('status');

    Mail::assertQueued(ResetPasswordMail::class, fn (ResetPasswordMail $mail) => $mail->hasTo($user->email));
});

test('forgot password does not send mail for suspended user but shows generic message', function () {
    Mail::fake();

    User::factory()->create([
        'email' => 'suspended@example.com',
        'role' => UserRole::STUDENT,
        'status' => UserStatus::SUSPENDED,
    ]);

    $this->post(route('password.email'), ['email' => 'suspended@example.com'])
        ->assertRedirect()
        ->assertSessionHas('status');

    Mail::assertNothingQueued();
});

test('user can reset password with valid token', function () {
    $user = User::factory()->create([
        'email' => 'newpass@example.com',
        'role' => UserRole::STUDENT,
        'password' => Hash::make('OldPass123!'),
        'must_reset_password' => true,
    ]);

    $token = Password::createToken($user);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => 'newpass@example.com',
        'password' => 'NewSecure123!',
        'password_confirmation' => 'NewSecure123!',
    ])->assertRedirect(route('password.reset.success'));

    $user->refresh();
    expect($user->must_reset_password)->toBeFalse();
    expect(Hash::check('NewSecure123!', $user->password))->toBeTrue();
});

test('reset password rejects invalid tokens', function () {
    $this->from(route('password.reset', ['token' => 'bad-token', 'email' => 'missing@example.com']))
        ->post(route('password.update'), [
            'token' => 'bad-token',
            'email' => 'missing@example.com',
            'password' => 'NewSecure123!',
            'password_confirmation' => 'NewSecure123!',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('email');
});

test('reset password blocks suspended accounts', function () {
    $user = User::factory()->suspended()->create([
        'email' => 'suspended-reset@example.com',
    ]);
    $token = Password::createToken($user);

    expect($user->isSuspended())->toBeTrue();

    $this->from(route('password.reset', ['token' => $token, 'email' => $user->email]))
        ->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewSecure123!',
            'password_confirmation' => 'NewSecure123!',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('email');
});

test('reset password rejects weak passwords', function () {
    $user = User::factory()->create(['email' => 'weak@example.com']);
    $token = Password::createToken($user);

    $this->from(route('password.reset', ['token' => $token, 'email' => 'weak@example.com']))
        ->post(route('password.update'), [
            'token' => $token,
            'email' => 'weak@example.com',
            'password' => 'weakpass',
            'password_confirmation' => 'weakpass',
        ])
        ->assertSessionHasErrors('password');
});
