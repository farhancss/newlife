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
];
