<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Add-On Catalog
    |--------------------------------------------------------------------------
    |
    | Available add-ons shown to students. Payment is completed externally on
    | the Squarespace storefront (newlifecampus.com), so each entry deep-links
    | to its product page. Pricing/title/description were captured from the
    | live storefront and are hardcoded here for now; a future phase may sync
    | these via the Squarespace catalog API.
    |
    | price_cents keeps money as integers to avoid float rounding issues.
    |
    */

    'catalog' => [
        [
            'slug' => 'full-service-summer-storage',
            'name' => 'Full-Service Summer Storage',
            'price_cents' => 70000,
            'description' => 'We pick everything up at the end of the semester, store it securely over the summer, and deliver it back for fall move-in. No hauling items home, no repacking, no starting from scratch next year.',
            'icon' => 'storage',
            'url' => 'https://www.newlifecampus.com/reserve-packages/p/essential-package-zzsjx',
        ],
        [
            'slug' => 'additional-container',
            'name' => 'Additional Container',
            'price_cents' => 17500,
            'description' => 'Need more space? Add extra containers for additional clothing, decor, or larger setups. Flexible capacity without upgrading your entire package.',
            'icon' => 'box',
            'url' => 'https://www.newlifecampus.com/reserve-packages/p/essential-package-zzsjx-lgsbn-ppckn',
        ],
        [
            'slug' => 'package-receiving-in-room-delivery',
            'name' => 'Package Receiving & In-Room Delivery',
            'price_cents' => 19500,
            'description' => "Ship your items ahead of time and we'll receive them and place them directly in your dorm. No waiting on mailrooms, no carrying packages across campus.",
            'icon' => 'truck',
            'url' => 'https://www.newlifecampus.com/reserve-packages/p/essential-package-zzsjx-srb8l-rgc4r',
        ],
        [
            'slug' => 'packing-material-removal',
            'name' => 'Packing Material Removal',
            'price_cents' => 12500,
            'description' => "We remove boxes, wrapping, and leftover packing materials after your move-in is complete. You're left with a clean, organized space from day one.",
            'icon' => 'star',
            'url' => 'https://www.newlifecampus.com/reserve-packages/p/essential-package-zzsjx-lgsbn',
        ],
        [
            'slug' => 'protection-coverage',
            'name' => 'Protection Coverage',
            'price_cents' => 9500,
            'description' => 'Added peace of mind while your items are in transit and storage. Coverage is designed to protect your belongings so you don\'t have to worry about the "what if."',
            'icon' => 'shield',
            'url' => 'https://www.newlifecampus.com/reserve-packages/p/essential-package-zzsjx-srb8l',
        ],
    ],

];
