<?php

use App\Enums\ContainerStatus;
use App\Enums\RetailPackageStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Container;
use App\Models\ContainerStatusHistory;
use App\Models\RetailPackage;
use App\Models\SquarespaceOrder;
use App\Models\SquarespaceOrderItem;
use App\Models\User;
use App\Policies\RetailPackagePolicy;
use App\Services\UserStatusService;

it('transitions user status through its lifecycle', function () {
    $service = app(UserStatusService::class);

    $suspended = User::factory()->create(['status' => UserStatus::SUSPENDED]);
    expect($service->markInvited($suspended)->fresh()->status)->toBe(UserStatus::INVITED);

    $active = User::factory()->create(['status' => UserStatus::ACTIVE]);
    expect($service->markInvited($active)->status)->toBe(UserStatus::ACTIVE);

    $invited = User::factory()->create(['status' => UserStatus::INVITED]);
    expect($service->markInvited($invited)->status)->toBe(UserStatus::INVITED)
        ->and($service->markPasswordChanged($invited)->fresh()->status)->toBe(UserStatus::INCOMPLETE);

    $alreadyActive = User::factory()->create(['status' => UserStatus::ACTIVE]);
    expect($service->markPasswordChanged($alreadyActive)->status)->toBe(UserStatus::ACTIVE);

    $incomplete = User::factory()->create(['status' => UserStatus::INCOMPLETE]);
    expect($service->markOnboardingComplete($incomplete)->fresh()->status)->toBe(UserStatus::ACTIVE);

    $suspended2 = User::factory()->create(['status' => UserStatus::SUSPENDED]);
    expect($service->markOnboardingComplete($suspended2)->status)->toBe(UserStatus::SUSPENDED)
        ->and($service->markIncomplete($suspended2)->status)->toBe(UserStatus::SUSPENDED);

    $invited2 = User::factory()->create(['status' => UserStatus::INVITED]);
    expect($service->markIncomplete($invited2)->status)->toBe(UserStatus::INVITED);

    $active2 = User::factory()->create(['status' => UserStatus::ACTIVE]);
    expect($service->markIncomplete($active2)->fresh()->status)->toBe(UserStatus::INCOMPLETE);
});

it('enforces the retail package policy', function () {
    $policy = new RetailPackagePolicy();

    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    expect($policy->before($admin, 'view'))->toBeTrue();

    [$owner, $profile] = completeStudent();
    expect($policy->before($owner, 'view'))->toBeNull();

    $editable = RetailPackage::query()->create([
        'student_profile_id' => $profile->id,
        'retailer' => 'Amazon',
        'description' => 'A',
        'tracking_number' => 'T1',
        'status' => RetailPackageStatus::LOGGED,
    ]);
    $locked = RetailPackage::query()->create([
        'student_profile_id' => $profile->id,
        'retailer' => 'Amazon',
        'description' => 'B',
        'tracking_number' => 'T2',
        'status' => RetailPackageStatus::RECEIVED_AT_HUB,
    ]);

    expect($policy->view($owner, $editable))->toBeTrue()
        ->and($policy->update($owner, $editable))->toBeTrue()
        ->and($policy->delete($owner, $editable))->toBeTrue()
        ->and($policy->update($owner, $locked))->toBeFalse();

    [$stranger] = completeStudent();
    expect($policy->view($stranger, $editable))->toBeFalse();
});

it('formats squarespace order and item totals', function () {
    $order = SquarespaceOrder::query()->create([
        'squarespace_order_id' => 'ord-1',
        'currency' => 'USD',
        'grand_total_cents' => 12500,
    ]);
    expect($order->formattedTotal())->toBe('USD 125.00');

    $noCurrency = SquarespaceOrder::query()->create([
        'squarespace_order_id' => 'ord-2',
        'grand_total_cents' => 5000,
    ]);
    expect($noCurrency->formattedTotal())->toBe('$50.00');

    $empty = SquarespaceOrder::query()->create(['squarespace_order_id' => 'ord-3']);
    expect($empty->formattedTotal())->toBe('—');

    $item = SquarespaceOrderItem::query()->create([
        'squarespace_order_id' => $order->id,
        'product_name' => 'Move Package',
        'quantity' => 1,
        'total_price_cents' => 9900,
    ]);
    expect($item->formattedTotal())->toBe('$99.00')
        ->and($item->order->id)->toBe($order->id);

    $itemNoTotal = SquarespaceOrderItem::query()->create([
        'squarespace_order_id' => $order->id,
        'product_name' => 'Freebie',
        'quantity' => 1,
    ]);
    expect($itemNoTotal->formattedTotal())->toBe('—');
});

it('labels a container status history entry', function () {
    [, $profile] = completeStudent();
    $container = Container::query()->create([
        'student_profile_id' => $profile->id,
        'code' => 'CTN-HIST-1',
        'status' => ContainerStatus::SHIPPED_TO_HOME,
        'source' => Container::SOURCE_MOVE,
    ]);

    $history = ContainerStatusHistory::query()->create([
        'container_id' => $container->id,
        'to_status' => ContainerStatus::SHIPPED_TO_HOME,
        'created_at' => now(),
    ]);

    expect($history->toStatusLabel())->toBe('Shipped to Home')
        ->and($history->container->id)->toBe($container->id);
});
