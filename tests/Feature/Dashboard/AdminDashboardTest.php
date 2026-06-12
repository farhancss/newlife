<?php

use App\Enums\ContainerStatus;
use App\Enums\UserRole;
use App\Models\Container;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\NewLifeIdGenerator;

function makeDashboardStudent(): StudentProfile
{
    $user = User::factory()->create(['role' => UserRole::STUDENT]);

    return StudentProfile::query()->create([
        'user_id' => $user->id,
        'new_life_id' => app(NewLifeIdGenerator::class)->generate(),
        'first_name' => 'Dash',
        'last_name' => 'Tester',
        'onboarding_completed_at' => now(),
    ]);
}

test('admin dashboard renders live metrics and upcoming deliveries', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $profile = makeDashboardStudent();

    Container::query()->create([
        'student_profile_id' => $profile->id,
        'code' => 'CTN-DASH1',
        'status' => ContainerStatus::SCHEDULED_FOR_DORM_DELIVERY,
        'location' => 'Norfolk, VA',
        'ship_by_date' => now()->addDays(3)->toDateString(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Dashboard Overview')
        ->assertSee('Total Students')
        ->assertSee('CTN-DASH1')
        ->assertSee('Scheduled for Dorm Delivery');
});

test('admin dashboard works with no data', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('No deliveries scheduled in the next 7 days.');
});
