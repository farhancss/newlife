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

];
