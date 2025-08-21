<?php

namespace Tests\Feature\Livewire\Page;

use App\Enums\Page\Status;
use App\Livewire\Page\ViewPage;
use App\Models\Page;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ViewPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_views_published_page(): void
    {
        $pageTitle = 'My Test Page Title';
        $pageContent = 'This is the content of my test page.';

        $page = Page::factory()->create([
            'status' => Status::Publish,
            'title' => ['en' => $pageTitle],
            'content' => ['en' => $pageContent],
        ]);

        Livewire::test(ViewPage::class, ['record' => $page])
            ->assertStatus(200)
            ->assertSee($pageTitle)
            ->assertSee($pageContent);
    }

    public function test_guest_views_draft_page(): void
    {
        $page = Page::factory()->create(['status' => Status::Draft]);

        Livewire::test(ViewPage::class, ['record' => $page])
            ->assertStatus(404);
    }

    public function test_unauthorized_user_views_draft_page(): void
    {
        $user = User::factory()->create();
        $page = Page::factory()->create(['status' => Status::Draft]);

        $this->actingAs($user);

        Livewire::test(ViewPage::class, ['record' => $page])
            ->assertStatus(404);
    }

    public function test_authorized_user_views_draft_page(): void
    {
        $user = User::factory()->create();
        $pageTitle = 'My Draft Page Title';
        $pageContent = 'This is the content of my draft page.';
        $page = Page::factory()->create([
            'status' => Status::Draft,
            'creator_id' => $user->id,
            'title' => ['en' => $pageTitle],
            'content' => ['en' => $pageContent],
        ]);

        // Create and assign 'update_page' permission
        $permission = Permission::firstOrCreate(['name' => 'update_page']);
        $user->givePermissionTo($permission);

        $this->actingAs($user);

        Livewire::test(ViewPage::class, ['record' => $page])
            ->assertStatus(200)
            ->assertSee($pageTitle)
            ->assertSee($pageContent);
    }

    public function test_actions_for_authorized_user(): void
    {
        $user = User::factory()->create();
        $pageTitle = 'My Action Page Title';
        $pageContent = 'This is the content for my action page.';
        $page = Page::factory()->create([
            'status' => Status::Draft,
            'creator_id' => $user->id,
            'title' => ['en' => $pageTitle],
            'content' => ['en' => $pageContent],
        ]);

        // Create and assign 'update_page' permission
        $permission = Permission::firstOrCreate(['name' => 'update_page']);
        $user->givePermissionTo($permission);

        $this->actingAs($user);

        $livewireComponent = Livewire::test(ViewPage::class, ['record' => $page]);
        $livewireComponent->assertSeeHtml(route('filament.admin.resources.pages.edit', ['record' => $page->id]));
        $livewireComponent->assertSeeHtml('Edit');
    }

    public function test_no_actions_for_guest_or_unauthorized_user(): void
    {
        $page = Page::factory()->create(['status' => Status::Publish]);

        // Guest
        Livewire::test(ViewPage::class, ['record' => $page])
            ->assertDontSeeHtml('Edit');

        // Unauthorized user
        $user = User::factory()->create();
        $this->actingAs($user);
        Livewire::test(ViewPage::class, ['record' => $page])
            ->assertDontSeeHtml('Edit');
    }
}
