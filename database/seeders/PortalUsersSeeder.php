<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PortalUsersSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('Admin@123');

        User::updateOrCreate(
            ['email' => 'student@demo.com'],
            [
                'name' => 'Demo Student',
                'role' => UserRole::STUDENT,
                'password' => $password,
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@demo.com'],
            [
                'name' => 'Demo Admin',
                'role' => UserRole::ADMIN,
                'password' => $password,
            ]
        );
    }
}
