<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\Role;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_one_role_per_role_name(): void
    {
        $this->seed(RoleSeeder::class);

        $this->assertEqualsCanonicalizing(
            RoleName::cases(),
            Role::all()->pluck('name')->all(),
        );
    }

    public function test_seeder_can_be_run_twice_without_duplicating_roles(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->assertSame(count(RoleName::cases()), Role::count());
    }
}
