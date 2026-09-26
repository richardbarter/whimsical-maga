<?php

namespace Tests\Feature\Admin;

use App\Models\Background;
use App\Models\Category;
use App\Models\Quote;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_real_counts(): void
    {
        Quote::factory()->count(3)->create();
        Quote::factory()->create()->delete();
        Background::factory()->count(2)->create();
        Tag::factory()->count(4)->create();
        Category::factory()->count(1)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Dashboard')
                ->where('quotesCount', 3)
                ->where('backgroundsCount', 2)
                ->where('tagsCount', 4)
                ->where('categoriesCount', 1)
            );
    }

    public function test_non_admins_cannot_view_the_dashboard(): void
    {
        $this->actingAs(User::factory()->asUser()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }
}
