<?php

use App\Services\AccountProvisioningService;
use App\Services\Squarespace\SquarespaceApiClient;
use Illuminate\Support\Facades\Mail;

it('inspects a squarespace order in dry-run mode', function () {
    $order = realOrderPayload();

    $this->mock(SquarespaceApiClient::class, function ($mock) use ($order) {
        $mock->shouldReceive('getOrder')
            ->once()
            ->with('order-123')
            ->andReturn($order);
    });

    $this->artisan('squarespace:inspect-order', ['orderId' => 'order-123'])
        ->assertSuccessful()
        ->expectsOutputToContain('ORDER PAYLOAD')
        ->expectsOutputToContain('ORDER → ONBOARDING MAPPING');
});

it('reports api failures when inspecting an order', function () {
    $this->mock(SquarespaceApiClient::class, function ($mock) {
        $mock->shouldReceive('getOrder')
            ->once()
            ->andThrow(new RuntimeException('API unavailable'));
    });

    $this->artisan('squarespace:inspect-order', ['orderId' => 'bad-order'])
        ->assertFailed()
        ->expectsOutputToContain('Order request failed');
});

it('provisions a student when inspect-order runs with --provision', function () {
    Mail::fake();

    $order = realOrderPayload(['customerEmail' => 'inspect-provision@example.com']);

    $this->mock(SquarespaceApiClient::class, function ($mock) use ($order) {
        $mock->shouldReceive('getOrder')
            ->once()
            ->with('order-provision')
            ->andReturn($order);
    });

    $this->artisan('squarespace:inspect-order', [
        'orderId' => 'order-provision',
        '--provision' => true,
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('Provisioning student account');

    expect(\App\Models\User::query()->where('email', 'inspect-provision@example.com')->exists())->toBeTrue();
});

it('reports provisioning failures during inspect-order --provision', function () {
    $order = realOrderPayload();

    $this->mock(SquarespaceApiClient::class, function ($mock) use ($order) {
        $mock->shouldReceive('getOrder')->once()->andReturn($order);
    });

    $this->mock(AccountProvisioningService::class, function ($mock) {
        $mock->shouldReceive('provisionFromOrder')
            ->once()
            ->andThrow(new RuntimeException('Provisioning failed'));
    });

    $this->artisan('squarespace:inspect-order', [
        'orderId' => 'order-123',
        '--provision' => true,
    ])
        ->assertFailed()
        ->expectsOutputToContain('Provisioning failed');
});

it('renders city line fallbacks for sparse billing addresses', function () {
    $order = realOrderPayload([
        'billingAddress' => [
            'firstName' => 'Min',
            'lastName' => 'Addr',
            'address1' => '',
            'city' => '',
            'state' => '',
            'postalCode' => '',
            'countryCode' => '',
        ],
    ]);

    $this->mock(SquarespaceApiClient::class, function ($mock) use ($order) {
        $mock->shouldReceive('getOrder')->once()->andReturn($order);
    });

    $this->artisan('squarespace:inspect-order', ['orderId' => 'sparse-order'])
        ->assertSuccessful()
        ->expectsOutputToContain('—');
});
