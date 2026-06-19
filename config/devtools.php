<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Developer Tools
    |--------------------------------------------------------------------------
    |
    | A temporary, password-protected admin screen for running maintenance
    | console commands (student onboarding, add-on purchases) from the browser.
    | Disabled by default — enable explicitly via the environment. When disabled,
    | the routes return 404.
    |
    */

    'enabled' => env('DEV_TOOLS_ENABLED', false),

];
