<?php

use App\Enums\ContainerStatus;
use App\Enums\PackageTier;
use App\Models\Container;
use App\Models\Package;
use App\Services\StudentPackageService;

beforeEach(function () {
    $this->service = app(StudentPackageService::class);
});

it('resolves a package from a loaded relation, id, tier, or returns null', function () {
    [, $profile] = completeStudent();

    expect($this->service->resolve($profile))->toBeNull();

    $legacy = Package::query()->where('slug', 'legacy')->firstOrFail();

    $profile->package_id = $legacy->id;
    $profile->save();
    expect($this->service->resolve($profile->fresh())->id)->toBe($legacy->id);

    $byTier = (clone $profile);
    $byTier->package_id = null;
    $byTier->package_tier = 'legacy';
    expect($this->service->resolve($byTier)->slug)->toBe('legacy');

    $profile->load('package');
    expect($this->service->resolve($profile)->id)->toBe($legacy->id);
});

it('assigns a package from a tier and from a package model', function () {
    [, $profile] = completeStudent();

    $assigned = $this->service->assignFromTier($profile, PackageTier::LEGACY);
    expect($assigned->package_tier)->toBe(PackageTier::LEGACY)
        ->and($assigned->package_id)->not->toBeNull();

    $unknown = $this->service->assignFromTier($profile->fresh(), 'mystery');
    expect($unknown->package_tier)->toBeNull()
        ->and($unknown->package_id)->toBeNull();

    $essential = Package::query()->where('slug', 'essential')->firstOrFail();
    $fromPackage = $this->service->assignFromPackage($profile->fresh(), $essential);
    expect($fromPackage->package_id)->toBe($essential->id);
});

it('computes container allowance and remaining slots', function () {
    [, $profile] = completeStudent();
    $this->service->assignFromTier($profile, PackageTier::LEGACY);
    $profile = $profile->fresh();

    $allowance = $this->service->containerAllowance($profile);
    expect($allowance)->toBeGreaterThan(0)
        ->and($this->service->containersAssigned($profile))->toBe(0)
        ->and($this->service->canAssignContainer($profile))->toBeTrue();

    Container::query()->create([
        'student_profile_id' => $profile->id,
        'code' => 'CTN-PKG-1',
        'status' => ContainerStatus::CONTAINER_PREPARED,
        'source' => Container::SOURCE_MOVE,
    ]);

    expect($this->service->containersAssigned($profile))->toBe(1)
        ->and($this->service->containersRemaining($profile))->toBe($allowance - 1);
});

it('falls back to an allowance of one without a package', function () {
    [, $profile] = completeStudent();

    expect($this->service->containerAllowance($profile))->toBe(1);
});

it('describes the move phases and phase progress', function () {
    expect($this->service->movePhases())->toHaveCount(6);

    $progress = $this->service->phaseProgressFor(ContainerStatus::SHIPPED_TO_HOME);
    $current = collect($progress)->firstWhere('current', true);

    expect($current['key'])->toBe('to_home')
        ->and($progress[0]['reached'])->toBeTrue();
});
