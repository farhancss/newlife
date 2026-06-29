<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\ContainerPhoto;
use App\Models\NotificationPreference;
use App\Models\Package;
use App\Models\PortalNotification;
use App\Models\RetailPackage;
use App\Models\RetailPackageStatusHistory;
use App\Models\SquarespaceCredential;
use App\Models\SquarespaceOrder;
use App\Models\StudentAddOn;
use App\Models\StudentSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

it('exposes belongs-to relations across models', function () {
    expect((new ContainerPhoto())->uploadedBy())->toBeInstanceOf(BelongsTo::class)
        ->and((new NotificationPreference())->user())->toBeInstanceOf(BelongsTo::class)
        ->and((new PortalNotification())->createdBy())->toBeInstanceOf(BelongsTo::class)
        ->and((new RetailPackage())->createdBy())->toBeInstanceOf(BelongsTo::class)
        ->and((new RetailPackageStatusHistory())->changedBy())->toBeInstanceOf(BelongsTo::class)
        ->and((new SquarespaceCredential())->connectedBy())->toBeInstanceOf(BelongsTo::class)
        ->and((new SquarespaceOrder())->studentProfile())->toBeInstanceOf(BelongsTo::class)
        ->and((new StudentAddOn())->activatedBy())->toBeInstanceOf(BelongsTo::class)
        ->and((new StudentSubscription())->studentProfile())->toBeInstanceOf(BelongsTo::class)
        ->and((new Package())->studentProfiles())->toBeInstanceOf(HasMany::class)
        ->and((new User())->notificationPreference())->toBeInstanceOf(HasOne::class);
});

it('scopes unread portal notifications', function () {
    expect(PortalNotification::query()->unread())->not->toBeNull();
});

it('treats a credential with no known expiry as expired', function () {
    $credential = new SquarespaceCredential(['access_token' => 'x']);
    $credential->expires_at = null;

    expect($credential->isExpired())->toBeTrue();
});

it('shortens package labels and reports user role/status helpers', function () {
    $essential = new Package(['slug' => 'essential']);
    $summit = new Package(['slug' => 'summit']);
    expect($essential->shortLabel())->toBe('Essential')
        ->and($summit->shortLabel())->toBe('Summit');

    $student = new User(['role' => UserRole::STUDENT, 'status' => UserStatus::ACTIVE]);
    expect($student->isStudent())->toBeTrue()
        ->and($student->isActive())->toBeTrue();

    $admin = new User(['role' => UserRole::ADMIN, 'status' => UserStatus::SUSPENDED]);
    expect($admin->isStudent())->toBeFalse()
        ->and($admin->isActive())->toBeFalse();
});
