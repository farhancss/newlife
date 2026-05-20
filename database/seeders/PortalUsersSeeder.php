<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\HousingInfo;
use App\Models\ParentGuardian;
use App\Models\ShippingAddress;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\NewLifeIdGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PortalUsersSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('Admin@123');
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
            'package_tier' => 'standard',
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
}
