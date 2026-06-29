<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackagesSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'slug' => 'essential',
                'name' => 'Essential Package',
                'tagline' => 'Simple, stress-free move-in.',
                'price_cents' => 135000,
                'container_count' => 3,
                'includes_move_out_cycle' => false,
                'includes_storage' => false,
                'allows_retail_packages' => false,
                'max_retail_packages' => 0,
                'is_featured' => false,
                'sort_order' => 1,
                'features' => [
                    'Pre-arrival delivery to student housing',
                    '3 storage & shipping containers included',
                    'Add-on move-out, storage, and return delivery available',
                ],
            ],
            [
                'slug' => 'summit',
                'name' => 'Summit Package',
                'tagline' => 'The complete move & storage solution for the school year.',
                'price_cents' => 230000,
                'container_count' => 5,
                'includes_move_out_cycle' => true,
                'includes_storage' => true,
                'allows_retail_packages' => false,
                'max_retail_packages' => 0,
                'is_featured' => true,
                'sort_order' => 2,
                'features' => [
                    'Pre-arrival delivery + 5 containers included',
                    'Move-out pickup, summer storage, and return delivery included',
                    'Protection coverage and New Life merchandise included',
                ],
            ],
            [
                'slug' => 'legacy',
                'name' => 'Legacy Package',
                'tagline' => 'Premium, all-inclusive.',
                'price_cents' => 310000,
                'container_count' => 7,
                'includes_move_out_cycle' => true,
                'includes_storage' => true,
                'allows_retail_packages' => true,
                'max_retail_packages' => 5,
                'is_featured' => false,
                'sort_order' => 3,
                'features' => [
                    'Everything in Summit, plus priority scheduling and support',
                    '7 containers + package receiving & in-room delivery',
                    'Packing material removal and curated Room Reveal on arrival',
                ],
            ],
        ];

        foreach ($packages as $data) {
            Package::query()->updateOrCreate(
                ['slug' => $data['slug']],
                $data,
            );
        }
    }
}
