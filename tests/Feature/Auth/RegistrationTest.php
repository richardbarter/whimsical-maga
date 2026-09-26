<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        app('cache')->store()->flush();
        config(['auth.registration_enabled' => true]);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('home', absolute: false));
    }

    public function test_registered_password_is_stored_hashed(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $storedPassword = User::where('email', 'test@example.com')->value('password');

        $this->assertNotSame('password', $storedPassword);
        $this->assertTrue(Hash::check('password', $storedPassword));
    }

    public function test_registration_ignores_a_submitted_role_id(): void
    {
        $adminRoleId = Role::where('name', RoleName::Admin)->value('id');

        $this->post('/register', [
            'name' => 'Sneaky User',
            'email' => 'sneaky@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role_id' => $adminRoleId,
        ]);

        $user = User::where('email', 'sneaky@example.com')->firstOrFail();
        $this->assertSame(RoleName::User, $user->role->name);
        $this->assertFalse($user->isAdmin());
    }

    public function test_registration_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post('/register', [
                'name' => 'Test User',
                'email' => "user{$i}@example.com",
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);
        }

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'final@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(429);
    }

    public function test_registration_screen_returns_404_when_registration_is_disabled(): void
    {
        config(['auth.registration_enabled' => false]);

        $this->get('/register')->assertNotFound();
    }

    public function test_registration_submission_returns_404_when_registration_is_disabled(): void
    {
        config(['auth.registration_enabled' => false]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertNotFound();
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }
}
