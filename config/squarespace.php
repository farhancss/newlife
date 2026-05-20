<?php

return [
    'client_id' => env('SQUARESPACE_CLIENT_ID'),
    'client_secret' => env('SQUARESPACE_CLIENT_SECRET'),
    'webhook_secret' => env('SQUARESPACE_WEBHOOK_SECRET'),
    'website_id' => env('SQUARESPACE_WEBSITE_ID'),
    'api_base_url' => env('SQUARESPACE_API_BASE_URL', 'https://api.squarespace.com/1.0'),
    'simulation_token' => env('SQUARESPACE_SIMULATION_TOKEN'),
    'skip_signature_verification' => env('SQUARESPACE_SKIP_SIGNATURE', false),

    'sku_tier_map' => [
        'SQSP-BASIC' => \App\Enums\PackageTier::BASIC,
        'SQSP-STANDARD' => \App\Enums\PackageTier::STANDARD,
        'SQSP-PREMIUM' => \App\Enums\PackageTier::PREMIUM,
    ],

    'webhook_topics' => [
        'contact.create',
        'contact.update',
        'address.create',
        'address.update',
        'order.create',
        'order.update',
    ],
];
