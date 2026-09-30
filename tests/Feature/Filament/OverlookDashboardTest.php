<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Pages\PageResource;
use App\Models\Page;
use App\Models\User;
use Awcodes\Overlook\OverlookPlugin;
use Awcodes\Overlook\Widgets\OverlookWidget;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\Support\FilamentPanelTestCase;

class OverlookDashboardTest extends FilamentPanelTestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_loads_with_overlook_registered(): void
    {
        $user = User::factory()->create();

        $this->grantPermissions($user, ['view_any_page']);
        $this->actingAs($user);

        $this->assertContains(
            OverlookWidget::class,
            Filament::getPanel('admin')->getWidgets(),
        );

        $this->assertContains(
            PageResource::class,
            OverlookPlugin::get()->getIncludes(),
        );

        $this->get(route('filament.admin.pages.dashboard'))
            ->assertOk();

        Livewire::test(OverlookWidget::class)
            ->assertSuccessful()
            ->assertSeeHtml('id="overlook-widget"')
            ->assertSee(PageResource::getPluralModelLabel(), escape: false);
    }

    public function test_page_metric_counts_only_records_visible_to_the_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->grantPermissions($user, ['view_any_page']);
        $this->actingAs($user);

        Page::factory()->count(2)->create([
            'creator_id' => $user->id,
        ]);

        Page::factory()->count(3)->create([
            'creator_id' => $otherUser->id,
        ]);

        $component = Livewire::test(OverlookWidget::class)
            ->assertSuccessful();

        $card = $this->pageCard($component);

        $this->assertNotNull($card);
        $this->assertSame('2', $card['raw_count']);
        $this->assertSame('2', $card['count']);

        $component->assertSeeHtml(
            '<span class="overlook-count text-gray-600 dark:text-gray-300 absolute leading-none bottom-3 end-4 text-3xl font-bold">2</span>',
        );
    }

    public function test_page_metric_includes_other_creators_with_view_all_permission(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->grantPermissions($user, [
            'view_any_page',
            'view_all_page',
        ]);

        $this->actingAs($user);

        Page::factory()->count(2)->create([
            'creator_id' => $user->id,
        ]);

        Page::factory()->count(3)->create([
            'creator_id' => $otherUser->id,
        ]);

        $component = Livewire::test(OverlookWidget::class)
            ->assertSuccessful();

        $card = $this->pageCard($component);

        $this->assertNotNull($card);
        $this->assertSame('5', $card['raw_count']);
        $this->assertSame('5', $card['count']);
    }

    public function test_page_metric_is_hidden_without_view_any_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Page::factory()->create([
            'creator_id' => $user->id,
        ]);

        $component = Livewire::test(OverlookWidget::class)
            ->assertSuccessful();

        $card = $this->pageCard($component);

        $this->assertNull($card);
    }

    /**
     * @param  Testable<OverlookWidget>  $component
     * @return array{raw_count: string, count: string}|null
     */
    private function pageCard(Testable $component): ?array
    {
        $data = $component->get('data');

        $this->assertIsArray($data);

        foreach ($data as $card) {
            $this->assertIsArray($card);

            if (($card['url'] ?? null) !== PageResource::getUrl('index')) {
                continue;
            }

            $this->assertArrayHasKey('raw_count', $card);
            $this->assertArrayHasKey('count', $card);
            $this->assertIsString($card['raw_count']);
            $this->assertIsString($card['count']);

            return [
                'raw_count' => $card['raw_count'],
                'count' => $card['count'],
            ];
        }

        return null;
    }
}
