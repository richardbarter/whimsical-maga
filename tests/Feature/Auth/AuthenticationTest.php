<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_login_screen_hides_sign_up_link_when_registration_is_disabled(): void
    {
        config(['auth.registration_enabled' => false]);

        $this->get('/login')
            ->assertInertia(fn ($page) => $page
                ->component('Auth/Login')
                ->where('canRegister', false)
            );
    }

    public function test_login_screen_shows_sign_up_link_when_registration_is_enabled(): void
    {
        config(['auth.registration_enabled' => true]);

        $this->get('/login')
            ->assertInertia(fn ($page) => $page->where('canRegister', true));
    }

    public function test_authenticated_admin_visiting_login_is_redirected_to_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/login')
            ->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_authenticated_regular_user_visiting_login_is_redirected_to_home(): void
    {
        $user = User::factory()->asUser()->create();

        $this->actingAs($user)
            ->get('/login')
            ->assertRedirect(route('home', absolute: false));
    }

    public function test_admin_users_are_redirected_to_admin_dashboard_after_login(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_regular_users_are_redirected_to_home_after_login(): void
    {
        $user = User::factory()->asUser()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('home', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->admin()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_login_is_locked_for_an_email_after_failures_spread_across_many_ip_addresses(): void
    {
        $user = User::factory()->admin()->create();

        for ($attempt = 1; $attempt <= 20; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.{$attempt}"])
                ->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.1.1'])
            ->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_the_per_ip_limit_still_applies_to_a_single_guesser(): void
    {
        $user = User::factory()->admin()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
