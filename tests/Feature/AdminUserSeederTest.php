<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_a_verified_admin_user(): void
    {
        config([
            'auth.admin_account.email' => 'seeded-admin@example.com',
            'auth.admin_account.password' => 'a-test-password',
        ]);

        $this->seed([RoleSeeder::class, AdminUserSeeder::class]);

        $admin = User::where('email', 'seeded-admin@example.com')->firstOrFail();

        $this->assertTrue($admin->isAdmin());
        $this->assertNotNull($admin->email_verified_at);
    }

    public function test_seeder_refuses_to_run_without_a_password(): void
    {
        config(['auth.admin_account.password' => null]);

        $this->expectException(RuntimeException::class);

        $this->seed([RoleSeeder::class, AdminUserSeeder::class]);
    }
}
