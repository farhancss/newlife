<?php

namespace Database\Seeders;

use App\Enums\AddOnStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\ContainerStatus;
use App\Models\Package;
use App\Models\HousingInfo;
use App\Models\ParentGuardian;
use App\Models\ShippingAddress;
use App\Models\StudentAddOn;
use App\Models\StudentProfile;
use App\Models\User;
use App\Enums\RetailPackageStatus;
use App\Services\ContainerWorkflowService;
use App\Services\NewLifeIdGenerator;
use App\Services\RetailPackageService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PortalUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Set default password
        $password = Hash::make('Admin@123');
        // Use stronger password for production and seed production-only admin user
        if (app()->isProduction()) {
            $password = Hash::make('@Portal@123!');
            // Add production admin user campus@newlifelogistix.com
            User::updateOrCreate(
                ['email' => 'campus@newlifelogistix.com'],
                [
                    'name' => 'Production Admin',
                    'role' => UserRole::ADMIN,
                    'status' => UserStatus::ACTIVE,
                    'password' => $password,
                    'must_reset_password' => true,
                ]
            );
        }

        $idGenerator = app(NewLifeIdGenerator::class);

        $student = User::updateOrCreate(
            ['email' => 'student@demo.com'],
            [
                'name' => 'Demo Student',
                'role' => UserRole::STUDENT,
                'status' => UserStatus::ACTIVE,
                'password' => $password,
                'must_reset_password' => false,
                'password_changed_at' => now(),
            ]
        );

        $profile = StudentProfile::query()->firstOrNew(['user_id' => $student->id]);

        if (!$profile->exists) {
            $profile->new_life_id = $idGenerator->generate();
        }

        $profile->fill([
            'first_name' => 'Demo',
            'last_name' => 'Student',
            'phone' => '757-555-0100',
            'school' => 'ODU',
            'incoming_year' => '2026',
            'package_tier' => 'summit',
            'package_id' => Package::query()->where('slug', 'summit')->value('id'),
            'move_container_quantity' => 5,
            'onboarding_step' => 5,
            'onboarding_completed_at' => $profile->onboarding_completed_at ?? now(),
        ]);
        $profile->save();

        ParentGuardian::query()->updateOrCreate(
            ['student_profile_id' => $profile->id],
            [
                'name' => 'Demo Parent',
                'email' => 'parent@demo.com',
                'phone' => '757-555-0100',
                'relationship' => 'Parent',
            ]
        );

        ShippingAddress::query()->updateOrCreate(
            ['student_profile_id' => $profile->id, 'type' => 'home'],
            [
                'line1' => '100 Main St',
                'city' => 'Norfolk',
                'region' => 'VA',
                'postal_code' => '23510',
                'country_code' => 'US',
            ]
        );

        HousingInfo::query()->updateOrCreate(
            ['student_profile_id' => $profile->id],
            [
                'university' => 'ODU',
                'residence_hall' => 'Gresham Hall',
                'move_in_date' => '2026-05-27',
            ]
        );

        $workflow = app(ContainerWorkflowService::class);
        $primary = $workflow->ensureMoveShipment($profile->fresh(['containers', 'package']) ?? $profile);

        if ($primary->status === ContainerStatus::CONTAINER_PREPARED) {
            $primary->forceFill([
                'location' => 'Norfolk',
                'outbound_tracking' => '794612345678',
            ])->save();

            $workflow->transition(
                $primary,
                ContainerStatus::SHIPPED_TO_HOME,
                null,
                'Demo seed data',
                force: true,
            );
        }

        // Demo: a Summit student who unlocked retail-package receiving by
        // purchasing an add-on (Summit doesn't include it out of the box).
        StudentAddOn::query()->updateOrCreate(
            ['student_profile_id' => $profile->id, 'add_on_slug' => StudentAddOn::SUMMER_STORAGE_SLUG],
            [
                'name' => 'Full-Service Summer Storage',
                'price_cents' => 19900,
                'squarespace_url' => 'https://www.newlifecampus.com/',
                'status' => AddOnStatus::ACTIVE,
                'requested_at' => now(),
                'activated_at' => now(),
            ]
        );

        $this->seedRetailPackages($profile->fresh() ?? $profile);

        User::updateOrCreate(
            ['email' => 'admin@demo.com'],
            [
                'name' => 'Demo Admin',
                'role' => UserRole::ADMIN,
                'status' => UserStatus::ACTIVE,
                'password' => $password,
                'must_reset_password' => false,
            ]
        );
    }

    private function seedRetailPackages(StudentProfile $profile): void
    {
        if ($profile->retailPackages()->exists()) {
            return;
        }

        $service = app(RetailPackageService::class);

        $samples = [
            ['retailer' => 'Amazon', 'description' => 'Mini fridge, dorm size', 'tracking_number' => 'TBA1234567890', 'status' => RetailPackageStatus::IN_TRANSIT],
            ['retailer' => 'Target', 'description' => 'Bedding kit, twin XL', 'tracking_number' => '1Z999AA10123456784', 'status' => RetailPackageStatus::RECEIVED_AT_HUB],
            ['retailer' => 'Wayfair', 'description' => '3-tier bookshelf', 'tracking_number' => 'FX785512369874', 'status' => RetailPackageStatus::DELIVERED_TO_DORM],
        ];

        foreach ($samples as $sample) {
            $package = $service->create($profile, [
                'retailer' => $sample['retailer'],
                'description' => $sample['description'],
                'tracking_number' => $sample['tracking_number'],
                'estimated_arrival' => now()->addDays(rand(2, 14))->toDateString(),
                'notes' => null,
            ]);

            $service->transition($package, $sample['status'], null, 'Demo seed data');
        }
    }
}
