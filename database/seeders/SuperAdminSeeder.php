<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@exerateanalytical.com'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('Exerate@Admin2024!'),
                'email_verified_at' => now(),
            ]
        );

        $admin->assignRole(UserRole::SuperAdmin->value);

        $this->command->info("SuperAdmin user created: {$admin->email}");
    }
}
