<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();

        $adminPassword = env('ADMIN_PASSWORD')
            ?: throw new \RuntimeException('ADMIN_PASSWORD environment variable must be set before running AdminUserSeeder.');

        User::create([
            'name' => 'Admin',
            'email' => env('ADMIN_EMAIL', 'admin@example.com'),
            'password' => bcrypt($adminPassword),
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
        ]);
    }
}
