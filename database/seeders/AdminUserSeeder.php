<?php

namespace Database\Seeders;

use App\Enums\RoleName;
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
        $adminRole = Role::where('name', RoleName::Admin)->firstOrFail();

        $adminPassword = config('auth.admin_account.password')
            ?: throw new \RuntimeException('ADMIN_PASSWORD environment variable must be set before running AdminUserSeeder.');

        $admin = new User([
            'name' => 'Admin',
            'email' => config('auth.admin_account.email'),
            'password' => bcrypt($adminPassword),
        ]);
        $admin->role()->associate($adminRole);
        $admin->email_verified_at = now();
        $admin->save();
    }
}
