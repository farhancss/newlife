<?php

use App\Models\SquarespaceOrder;
use App\Models\SquarespaceWebhookSubscription;
use App\Services\AccountProvisioningService;
use App\Services\Squarespace\SquarespaceOAuthService;
use App\Services\Squarespace\SquarespaceOrderImporter;
use App\Services\Squarespace\SquarespaceSignatureVerifier;

function makeProfile(string $email = 'buyer@example.com'): \App\Models\StudentProfile
{
    return app(AccountProvisioningService::class)->upsertFromContact([
        'contactId' => 'sq-contact-int-001',
        'firstName' => 'Casey',
        'lastName' => 'Buyer',
        'primaryEmail' => ['value' => $email],
    ], false);
}

test('order importer persists the full purchase with line items', function () {
    $profile = makeProfile();

    $order = [
        'id' => 'sq-order-int-001',
        'orderNumber' => '00042',
        'customerEmail' => 'buyer@example.com',
        'fulfillmentStatus' => 'PENDING',
        'grandTotal' => ['value' => '230.00', 'currency' => 'USD'],
        'subtotal' => ['value' => '210.00', 'currency' => 'USD'],
        'lineItems' => [
            [
                'id' => 'li-1',
                'productId' => 'prod-essential',
                'productName' => 'Essential Move Package',
                'sku' => 'SQSP-ESSENTIAL',
                'quantity' => 1,
                'unitPricePaid' => ['value' => '210.00', 'currency' => 'USD'],
            ],
        ],
        'createdOn' => '2026-06-29T10:00:00Z',
    ];

    $record = app(SquarespaceOrderImporter::class)->import($order, $profile);

    expect($record->squarespace_order_id)->toBe('sq-order-int-001')
        ->and($record->grand_total_cents)->toBe(23000)
        ->and($record->order_number)->toBe('00042')
        ->and($record->items)->toHaveCount(1);

    $item = $record->items->first();
    expect($item->product_name)->toBe('Essential Move Package')
        ->and($item->sku)->toBe('SQSP-ESSENTIAL')
        ->and($item->unit_price_cents)->toBe(21000)
        ->and($item->total_price_cents)->toBe(21000);
});

test('order importer activates an add-on mapped from a sku', function () {
    config(['squarespace.addon_sku_map' => ['SQSP-STORAGE' => 'full-service-summer-storage']]);

    $profile = makeProfile();

    $order = [
        'id' => 'sq-order-int-002',
        'lineItems' => [
            ['id' => 'li-1', 'productName' => 'Summer Storage', 'sku' => 'SQSP-STORAGE', 'quantity' => 1],
        ],
    ];

    app(SquarespaceOrderImporter::class)->import($order, $profile);

    expect($profile->addOns()->where('add_on_slug', 'full-service-summer-storage')->count())->toBe(1);

    // Re-importing the same order must not duplicate the add-on.
    app(SquarespaceOrderImporter::class)->import($order, $profile->fresh());
    expect($profile->addOns()->where('add_on_slug', 'full-service-summer-storage')->count())->toBe(1);
});

test('signature verifier accepts hex and base64 against a stored subscription secret', function () {
    config(['squarespace.webhook_secret' => null]);

    $secret = 'F3F9B981C78E7A6187E42853F6CE2804';
    SquarespaceWebhookSubscription::query()->create([
        'subscription_id' => 'sub-int-001',
        'endpoint_url' => 'https://example.test/api/webhooks/squarespace',
        'topics' => ['order.create'],
        'secret' => $secret,
    ]);

    $payload = '{"id":"abc","topic":"order.create"}';
    $hex = hash_hmac('sha256', $payload, $secret);
    $base64 = base64_encode(hash_hmac('sha256', $payload, $secret, true));

    $verifier = app(SquarespaceSignatureVerifier::class);

    expect($verifier->verify($payload, $hex))->toBeTrue()
        // Squarespace sends uppercase hex — must still verify.
        ->and($verifier->verify($payload, strtoupper($hex)))->toBeTrue()
        ->and($verifier->verify($payload, $base64))->toBeTrue()
        ->and($verifier->verify($payload, 'nope'))->toBeFalse();
});

test('oauth service builds an authorization url with the required params', function () {
    config([
        'squarespace.client_id' => 'client-123',
        'squarespace.client_secret' => 'secret-123',
        'squarespace.oauth.authorize_url' => 'https://login.squarespace.com/api/1/login/oauth/provider/authorize',
        'squarespace.oauth.redirect_uri' => 'https://portal.test/squarespace/callback',
        'squarespace.oauth.scopes' => ['website.orders.read', 'website.contacts'],
    ]);

    $url = app(SquarespaceOAuthService::class)->authorizationUrl('state-xyz');

    expect($url)->toContain('client_id=client-123')
        ->toContain('response_type=code')
        ->toContain('access_type=offline')
        ->toContain('state=state-xyz')
        ->toContain(urlencode('https://portal.test/squarespace/callback'))
        ->toContain(urlencode('website.orders.read,website.contacts'));
});
