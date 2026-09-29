<?php

namespace Tests\Feature\Livewire\Page;

use App\Enums\Page\Status;
use App\Models\Page;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AssertsLivewireEmbeds;
use Tests\TestCase;

class ViewPageRouteTest extends TestCase
{
    use AssertsLivewireEmbeds;
    use RefreshDatabase;

    public function test_published_page_renders_with_layout_for_guest(): void
    {
        $page = Page::factory()->create([
            'status' => Status::Publish,
            'title' => ['en' => 'Smoke Published Page'],
            'content' => ['en' => 'Smoke published content.'],
        ]);

        $response = $this->get(route('pages.show', $page))
            ->assertOk()
            ->assertSee('Smoke Published Page')
            ->assertSee('Smoke published content.')
            ->assertSee(__('navigation-menu.menu.guest'));

        $this->assertSeesLivewire($response, 'page.view-page', 'navigation-menu');
    }

    public function test_draft_page_returns_404_for_guest(): void
    {
        $page = Page::factory()->create(['status' => Status::Draft]);

        $this->get(route('pages.show', $page))
            ->assertNotFound();
    }

    public function test_draft_page_renders_for_user_passing_update_gate(): void
    {
        $user = User::factory()->create();
        $page = Page::factory()->create([
            'status' => Status::Draft,
            'creator_id' => $user->id,
            'title' => ['en' => 'Smoke Draft Page'],
            'content' => ['en' => 'Smoke draft content.'],
        ]);

        $permission = Permission::firstOrCreate(['name' => 'update_page']);
        $user->givePermissionTo($permission);

        $response = $this->actingAs($user)
            ->get(route('pages.show', $page))
            ->assertOk()
            ->assertSee('Smoke Draft Page')
            ->assertSee('Smoke draft content.');

        $this->assertSeesLivewire($response, 'page.view-page', 'navigation-menu');
    }

    public function test_pages_show_route_is_registered_once_with_expected_url(): void
    {
        $this->assertSame(
            url('/pages/sample-slug'),
            route('pages.show', ['record' => 'sample-slug'])
        );

        $matchingRoutes = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn ($route) => $route->getName() === 'pages.show');

        $this->assertCount(1, $matchingRoutes);
    }
}
