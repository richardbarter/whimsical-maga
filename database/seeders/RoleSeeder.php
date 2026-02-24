<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create([
            'name' => 'admin',
            'description' => 'Full access to all features and settings',
        ]);

        Role::create([
            'name' => 'moderator',
            'description' => 'Can approve quotes, manage tags/categories',
        ]);

        Role::create([
            'name' => 'user',
            'description' => 'Can submit quotes and view own submissions',
        ]);
    }
}
