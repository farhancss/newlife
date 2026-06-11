<?php

use App\Enums\PackageTier;
use App\Models\Package;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\NewLifeIdGenerator;
use App\Services\StudentPackageService;

test('three default packages are seeded', function () {
    expect(Package::query()->count())->toBe(3)
        ->and(Package::query()->where('slug', 'essential')->value('container_count'))->toBe(3)
        ->and(Package::query()->where('slug', 'summit')->value('container_count'))->toBe(5)
        ->and(Package::query()->where('slug', 'legacy')->value('container_count'))->toBe(7);
});

test('assign from tier links package and sets container allowance default', function () {
    $user = User::factory()->create();
    $profile = StudentProfile::query()->create([
        'user_id' => $user->id,
        'new_life_id' => app(NewLifeIdGenerator::class)->generate(),
    ]);

    $service = app(StudentPackageService::class);
    $service->assignFromTier($profile, PackageTier::SUMMIT);

    $profile->refresh();
    expect($profile->package_tier)->toBe('summit')
        ->and($profile->package_id)->toBe(Package::query()->where('slug', 'summit')->value('id'))
        ->and($profile->move_container_quantity)->toBe(5)
        ->and($service->containerAllowance($profile))->toBe(5);
});
