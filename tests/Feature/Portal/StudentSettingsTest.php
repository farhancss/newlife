<?php

use App\Models\NotificationPreference;

it('shows the student settings page with a preference', function () {
    [$user] = completeStudent();

    $this->actingAs($user)
        ->get(route('student.settings'))
        ->assertOk();

    expect(NotificationPreference::query()->where('user_id', $user->id)->exists())->toBeTrue();
});

it('saves notification preferences', function () {
    [$user] = completeStudent();

    $this->actingAs($user)
        ->put(route('student.settings.update'), [
            'email_enabled' => '1',
            'sms_enabled' => '1',
            'sms_number' => '757-555-0123',
            'parent_cc_enabled' => '1',
        ])
        ->assertRedirect(route('student.settings'))
        ->assertSessionHas('status');

    $preference = NotificationPreference::query()->where('user_id', $user->id)->first();

    expect($preference->email_enabled)->toBeTrue()
        ->and($preference->sms_enabled)->toBeTrue()
        ->and($preference->sms_number)->toBe('757-555-0123')
        ->and($preference->parent_cc_enabled)->toBeTrue();
});

it('treats unchecked toggles as disabled', function () {
    [$user] = completeStudent();

    $this->actingAs($user)
        ->put(route('student.settings.update'), [
            'sms_number' => '',
        ])
        ->assertRedirect(route('student.settings'));

    $preference = NotificationPreference::query()->where('user_id', $user->id)->first();

    expect($preference->email_enabled)->toBeFalse()
        ->and($preference->sms_enabled)->toBeFalse()
        ->and($preference->parent_cc_enabled)->toBeFalse();
});
