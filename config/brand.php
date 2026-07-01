<?php

return [
    'name' => env('BRAND_NAME', 'New Life Campus'),
    'tagline' => env('BRAND_TAGLINE', 'Campus Move-In Portal'),
    'mark' => env('BRAND_MARK', 'NL'),

    'colors' => [
        'primary' => '#0827be',
        'primary_dark' => '#061f98',
        'primary_light' => '#e9edfe',
        'navy' => '#040f5c',
        'ink' => '#101828',
        'body' => '#1f2937',
        'muted' => '#6b7280',
        'border' => '#e4e7ec',
        'surface' => '#ffffff',
        'background' => '#f4f5fa',
    ],

    'support' => [
        'email' => env('BRAND_SUPPORT_EMAIL', 'campus@newlifelogistix.com'),
        'phone' => env('BRAND_SUPPORT_PHONE'),
    ],

    'address' => env('BRAND_ADDRESS'),
    'website_url' => env('BRAND_WEBSITE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Maintenance / pre-launch page (503)
    |--------------------------------------------------------------------------
    |
    | Shown while the app is in maintenance mode (`php artisan down`). Override
    | the message at runtime with: php artisan down --message="Your note here"
    |
    */
    'maintenance' => [
        'title' => env('MAINTENANCE_TITLE', 'Hang tight!'),
        'message' => env('MAINTENANCE_MESSAGE', 'Something exciting is on the way! The New Life Campus portal is almost ready — your hub for tracking your move, completing onboarding, and staying updated every step of the way. Already purchased a package? You\'re all set. We\'ll email you an invitation with login instructions the moment we go live.'),
        'note' => env('MAINTENANCE_NOTE', 'Expected launch: July 1, 2026'),
        'code_label' => env('MAINTENANCE_CODE_LABEL', 'Almost here'),
    ],
];
