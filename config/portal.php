<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Container Photos
    |--------------------------------------------------------------------------
    |
    | Rules governing student-uploaded exterior container photos. Uploads are
    | only permitted while a container is in the "Customer Packing" status.
    |
    */

    'container_photos' => [
        'disk' => env('CONTAINER_PHOTO_DISK', 'public'),
        'max_per_container' => (int) env('CONTAINER_PHOTO_MAX', 5),
        'max_size_kb' => (int) env('CONTAINER_PHOTO_MAX_KB', 5120),
        'allowed_mimes' => ['jpeg', 'jpg', 'png'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Retail Packages
    |--------------------------------------------------------------------------
    |
    | Rules governing student-logged retail shipments. The active cap limits
    | how many non-delivered packages a student may track at once. Packages
    | become read-only for students once they reach "edit_lock_status".
    |
    */

    'retail_packages' => [
        'active_cap' => (int) env('RETAIL_PACKAGE_ACTIVE_CAP', 10),
        'edit_lock_status' => 'received_at_hub',
        'retailers' => ['Amazon', 'Walmart', 'Target', 'Wayfair', 'DHL', 'UPS', 'FedEx', 'Other'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Profile Avatars
    |--------------------------------------------------------------------------
    |
    | User-uploaded profile photos. Falls back to name initials when no photo
    | has been uploaded.
    |
    */

    'avatars' => [
        'disk' => env('AVATAR_DISK', 'public'),
        'max_size_kb' => (int) env('AVATAR_MAX_KB', 4096),
        'allowed_mimes' => ['jpeg', 'jpg', 'png', 'webp'],
    ],

];
