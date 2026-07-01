<?php

use App\Models\Package;
use App\Models\StudentAddOn;
use Illuminate\Support\Facades\Mail;

it('returns 404 when developer tools are disabled', function () {
    config(['devtools.enabled' => false]);

    $this->actingAs(makeAdmin())
        ->get(route('admin.dev-tools.index'))
        ->assertNotFound();
});

it('shows the developer tools page when enabled', function () {
    config(['devtools.enabled' => true]);

    $this->actingAs(makeAdmin())
        ->get(route('admin.dev-tools.index'))
        ->assertOk();
});

it('invites a student through the developer tools', function () {
    config(['devtools.enabled' => true]);
    Mail::fake();

    $this->actingAs(makeAdmin())
        ->post(route('admin.dev-tools.invite-student'), [
            'email' => 'devtool@example.com',
            'first_name' => 'Dev',
            'last_name' => 'Tool',
            'package' => Package::query()->value('slug'),
        ])
        ->assertRedirect(route('admin.dev-tools.index'))
        ->assertSessionHas('dev_result');
});

it('validates the invite-student form in developer tools', function () {
    config(['devtools.enabled' => true]);

    $this->actingAs(makeAdmin())
        ->post(route('admin.dev-tools.invite-student'), ['email' => ''])
        ->assertSessionHasErrors(['email', 'package']);
});

it('buys an add-on through the developer tools', function () {
    config(['devtools.enabled' => true]);
    Mail::fake();
    [$user] = completeStudent(['email' => 'devbuyer@example.com']);

    $this->actingAs(makeAdmin())
        ->post(route('admin.dev-tools.buy-addon'), [
            'student' => 'devbuyer@example.com',
            'slug' => 'protection-coverage',
        ])
        ->assertRedirect(route('admin.dev-tools.index'))
        ->assertSessionHas('dev_result');

    expect(StudentAddOn::query()->where('student_profile_id', $user->studentProfile->id)->exists())->toBeTrue();
});
