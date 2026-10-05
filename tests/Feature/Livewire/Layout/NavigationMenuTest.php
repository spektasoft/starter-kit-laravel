<?php

namespace Tests\Feature\Livewire\Layout;

use App\Enums\Page\Status;
use App\Models\Page;
use App\Models\User;
use Filament\Notifications\Livewire\DatabaseNotifications;
use Filament\Notifications\Livewire\Notifications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\AssertsLivewireEmbeds;
use Tests\TestCase;

class NavigationMenuTest extends TestCase
{
    use AssertsLivewireEmbeds;
    use RefreshDatabase;

    public function test_navigation_menu_renders_guest_menu_for_guest(): void
    {
        Livewire::test('navigation-menu')
            ->assertStatus(200)
            ->assertSee(__('navigation-menu.menu.guest'))
            ->assertSeeHtml(route('login'))
            ->assertDontSeeHtml('database-notifications');
    }

    public function test_navigation_menu_renders_user_menu_for_authenticated_user(): void
    {
        $user = User::factory()->create(['name' => 'Smoke Tester']);
        $this->actingAs($user);

        Livewire::test('navigation-menu')
            ->assertStatus(200)
            ->assertSee('Smoke Tester')
            ->assertSee(__('navigation-menu.menu.profile'))
            ->assertSeeHtml(route('profile.show'))
            ->assertSeeHtml('database-notifications');
    }

    public function test_layout_embeds_notifications_for_guest_without_database_notifications(): void
    {
        $response = $this->get(route('pages.show', $this->createPublishedPage()))
            ->assertOk();

        $this->assertSeesLivewire($response, 'navigation-menu', 'notifications');
        $this->assertDoesNotSeeLivewire($response, 'database-notifications');
    }

    public function test_layout_embeds_notifications_and_database_notifications_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('pages.show', $this->createPublishedPage()))
            ->assertOk();

        $this->assertSeesLivewire(
            $response,
            'navigation-menu',
            'notifications',
            'database-notifications',
        );
    }

    public function test_notifications_component_renders(): void
    {
        Livewire::test(Notifications::class)
            ->assertStatus(200);
    }

    public function test_database_notifications_component_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(DatabaseNotifications::class)
            ->assertStatus(200);
    }

    private function createPublishedPage(): Page
    {
        return Page::factory()->create(['status' => Status::Publish]);
    }
}
