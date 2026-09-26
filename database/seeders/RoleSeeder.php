<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds. Safe to re-run: existing roles are updated, not duplicated.
     */
    public function run(): void
    {
        foreach (RoleName::cases() as $roleName) {
            Role::updateOrCreate(
                ['name' => $roleName],
                ['description' => $roleName->description()],
            );
        }
    }
}
