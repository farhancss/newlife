<?php

use App\Mail\OnboardingCompleteMail;
use App\Mail\PasswordChangedMail;
use App\Mail\ResetPasswordMail;
use App\Mail\StudentInvitationMail;
use App\Models\User;

it('builds the onboarding complete mail', function () {
    $user = User::factory()->create();
    $mail = new OnboardingCompleteMail($user);

    expect($mail->envelope()->subject)->toContain('all set')
        ->and($mail->render())->not->toBe('');
});

it('builds the reset password mail', function () {
    $user = User::factory()->create();
    $mail = new ResetPasswordMail($user, 'https://portal.test/reset/token');

    expect($mail->envelope()->subject)->toContain('Reset')
        ->and($mail->render())->toContain('https://portal.test/reset/token');
});

it('builds the password changed mail for a first reset and a routine change', function () {
    $user = User::factory()->create();

    $first = new PasswordChangedMail($user, wasFirstReset: true);
    $routine = new PasswordChangedMail($user, wasFirstReset: false);

    expect($first->envelope()->subject)->toContain('set')
        ->and($routine->envelope()->subject)->toContain('updated')
        ->and($first->render())->not->toBe('')
        ->and($routine->render())->not->toBe('');
});

it('builds the student invitation mail', function () {
    $user = User::factory()->create();
    $mail = new StudentInvitationMail($user, 'Temp-Pass-123!');

    expect($mail->envelope()->subject)->toContain('Welcome')
        ->and($mail->render())->toContain('Temp-Pass-123!');
});
