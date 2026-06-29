<?php

use App\Models\StudentAddOn;
use App\Models\SquarespaceWebhookEvent;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('invites a new student and assigns a package', function () {
    Mail::fake();

    $this->artisan('portal:invite-student', [
        'email' => 'fresh@example.com',
        '--package' => 'legacy',
    ])->assertExitCode(0);

    expect(User::query()->where('email', 'fresh@example.com')->exists())->toBeTrue();
});

it('fails to invite with an invalid email', function () {
    $this->artisan('portal:invite-student', ['email' => 'not-an-email'])
        ->assertExitCode(1);
});

it('warns when inviting an existing user and skips unknown packages', function () {
    Mail::fake();
    User::factory()->create(['email' => 'dupe@example.com']);

    $this->artisan('portal:invite-student', [
        'email' => 'dupe@example.com',
        '--package' => 'mystery-tier',
        '--no-email' => true,
    ])->assertExitCode(0);
});

it('buys an add-on for a student by email and provisions a container', function () {
    Mail::fake();
    [$user] = completeStudent(['email' => 'buyer@example.com']);

    $this->artisan('portal:buy-addon', [
        'student' => 'buyer@example.com',
        'slug' => StudentAddOn::ADDITIONAL_CONTAINER_SLUG,
    ])->assertExitCode(0);

    $addOn = StudentAddOn::query()->where('student_profile_id', $user->studentProfile->id)->first();
    expect($addOn)->not->toBeNull()
        ->and($addOn->container_id)->not->toBeNull();
});

it('fails to buy an add-on for an unknown student', function () {
    $this->artisan('portal:buy-addon', [
        'student' => 'ghost@example.com',
        'slug' => StudentAddOn::ADDITIONAL_CONTAINER_SLUG,
    ])->assertExitCode(1);
});

it('fails to buy an unknown add-on slug', function () {
    completeStudent(['email' => 'buyer2@example.com']);

    $this->artisan('portal:buy-addon', [
        'student' => 'buyer2@example.com',
        'slug' => 'not-a-real-addon',
    ])->assertExitCode(1);
});

it('fails squarespace webhook management when not connected', function () {
    $this->artisan('squarespace:webhooks', ['action' => 'list'])
        ->assertExitCode(1);
});

it('fails to show a squarespace token when not connected', function () {
    $this->artisan('squarespace:token')->assertExitCode(1);
});

it('simulates a contact webhook synchronously', function () {
    Mail::fake();

    $this->artisan('squarespace:simulate', [
        'topic' => 'contact.create',
        '--email' => 'sim@example.com',
        '--sync' => true,
    ])->assertExitCode(0);

    expect(SquarespaceWebhookEvent::query()->where('topic', 'contact.create')->exists())->toBeTrue();
});

it('simulates an order webhook synchronously', function () {
    Mail::fake();

    $this->artisan('squarespace:simulate', [
        'topic' => 'order.create',
        '--email' => 'sim-order@example.com',
        '--sync' => true,
    ])->assertExitCode(0);

    expect(SquarespaceWebhookEvent::query()->where('topic', 'order.create')->exists())->toBeTrue();
});
