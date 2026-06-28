<?php

return [
    'client_id' => env('SQUARESPACE_CLIENT_ID'),
    'client_secret' => env('SQUARESPACE_CLIENT_SECRET'),

    // Private API key. Used as a Bearer token for read endpoints (orders,
    // contacts) when an OAuth connection is not yet established. Note: the
    // WebhookSubscriptions API rejects API keys and always requires OAuth.
    'api_key' => env('SQUARESPACE_API_KEY'),

    // Fallback HMAC secret. Once a webhook subscription is created via the API,
    // Squarespace returns a per-subscription secret which we store and prefer
    // (see App\Services\Squarespace\SquarespaceSignatureVerifier).
    'webhook_secret' => env('SQUARESPACE_WEBHOOK_SECRET'),

    'website_id' => env('SQUARESPACE_WEBSITE_ID'),
    'api_base_url' => env('SQUARESPACE_API_BASE_URL', 'https://api.squarespace.com/1.0'),
    'simulation_token' => env('SQUARESPACE_SIMULATION_TOKEN'),
    'skip_signature_verification' => env('SQUARESPACE_SKIP_SIGNATURE', false),

    /*
    |--------------------------------------------------------------------------
    | OAuth (Authorization Code grant)
    |--------------------------------------------------------------------------
    | Squarespace Commerce APIs (orders, contacts, webhook subscriptions)
    | require an OAuth access token — API keys are not accepted. We use the
    | authorization-code flow with offline access so we receive a refresh token
    | and can keep the access token fresh in the background.
    */
    'oauth' => [
        'authorize_url' => env('SQUARESPACE_OAUTH_AUTHORIZE_URL', 'https://login.squarespace.com/api/1/login/oauth/provider/authorize'),
        'token_url' => env('SQUARESPACE_OAUTH_TOKEN_URL', 'https://login.squarespace.com/api/1/login/oauth/provider/tokens'),

        // Must exactly match the redirect URL registered on your Squarespace app,
        // e.g. https://your-domain/squarespace/callback. Defaults to the named
        // squarespace.callback route when left blank.
        'redirect_uri' => env('SQUARESPACE_OAUTH_REDIRECT_URI'),

        // Space/comma separated scopes requested during authorization. These
        // cover reading orders, reading/writing contacts, and managing webhook
        // subscriptions for those topics.
        'scopes' => array_values(array_filter(array_map('trim', explode(',', (string) env(
            'SQUARESPACE_OAUTH_SCOPES',
            'website.orders.read,website.contacts'
        ))))),

        // Refresh the access token when it expires within this many seconds.
        'refresh_leeway' => 120,
    ],

    // Required on every Squarespace API request; describes this integration.
    'user_agent' => env('SQUARESPACE_USER_AGENT', 'NewLifeCampusPortal/1.0 (+https://www.newlifecampus.com)'),

    // Write request/response bodies for inbound webhooks and outbound API calls
    // to the dedicated "squarespace" log channel (viewable via Log Viewer) so
    // they can be inspected. Set false to silence.
    'logging_enabled' => env('SQUARESPACE_LOGGING_ENABLED', true),

    // The log channel webhook/API traffic is written to.
    'log_channel' => env('SQUARESPACE_LOG_CHANNEL', 'squarespace'),

    'sku_tier_map' => [
        'SQSP-ESSENTIAL' => \App\Enums\PackageTier::ESSENTIAL,
        'SQSP-SUMMIT' => \App\Enums\PackageTier::SUMMIT,
        'SQSP-LEGACY' => \App\Enums\PackageTier::LEGACY,
        // Legacy simulation / dev SKUs
        'SQSP-BASIC' => \App\Enums\PackageTier::ESSENTIAL,
        'SQSP-STANDARD' => \App\Enums\PackageTier::SUMMIT,
        'SQSP-PREMIUM' => \App\Enums\PackageTier::LEGACY,
    ],

    /*
    | Map storefront SKUs to add-on catalog slugs (see config/addons.php).
    | When an order line item matches, the add-on is activated for the student
    | automatically. Leave entries commented until the real SKUs are known.
    */
    'addon_sku_map' => [
        // 'SQSP-STORAGE' => 'full-service-summer-storage',
        // 'SQSP-EXTRA-CONTAINER' => 'additional-container',
        // 'SQSP-RECEIVING' => 'package-receiving-in-room-delivery',
        // 'SQSP-PACKING-REMOVAL' => 'packing-material-removal',
        // 'SQSP-PROTECTION' => 'protection-coverage',
    ],

    'webhook_topics' => [
        'contact.create',
        'contact.update',
        'contact.delete',
        'address.create',
        'address.update',
        'address.delete',
        'order.create',
        'order.update',
    ],
];
