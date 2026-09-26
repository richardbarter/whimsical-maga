<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedAuthPropsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_receives_null_auth_user(): void
    {
        $this->get(route('home'))
            ->assertInertia(fn ($page) => $page->where('auth.user', null));
    }

    public function test_admin_receives_is_admin_true(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('profile.edit'))
            ->assertInertia(fn ($page) => $page
                ->where('auth.user.id', $admin->id)
                ->where('auth.user.is_admin', true)
            );
    }

    public function test_regular_user_receives_is_admin_false(): void
    {
        $user = User::factory()->asUser()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertInertia(fn ($page) => $page->where('auth.user.is_admin', false));
    }

    public function test_shared_user_contains_only_the_fields_the_frontend_needs(): void
    {
        $user = User::factory()->asUser()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertInertia(fn ($page) => $page
                ->has('auth.user', fn ($sharedUser) => $sharedUser
                    ->where('name', $user->name)
                    ->where('email', $user->email)
                    ->has('id')
                    ->has('email_verified_at')
                    ->has('is_admin')
                )
            );
    }
}
