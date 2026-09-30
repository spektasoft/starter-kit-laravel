<?php

namespace Tests\Feature;

use App\Filament\Components\Modals\CuratorPanel;
use App\Models\Media;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class CuratorPanelMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * @return Testable<CuratorPanel>
     */
    private function openPanel(): Testable
    {
        return Livewire::test(CuratorPanel::class, [
            'settings' => [
                'directory' => 'media',
                'statePath' => 'data.image',
                'context' => 'test',
            ],
        ]);
    }

    /**
     * @param  Testable<CuratorPanel>  $component
     * @return array<int, string>
     */
    private function listedIds(Testable $component): array
    {
        /** @var array<int, array{id: string}> $files */
        $files = $component->get('files');

        return array_map(fn (array $file): string => $file['id'], $files);
    }

    public function test_panel_lists_only_own_media_without_view_all_permission(): void
    {
        $user = User::factory()->create();
        $own = Media::factory()->create(['creator_id' => $user->id, 'directory' => 'media']);
        $foreign = Media::factory()->create(['directory' => 'media']);

        $this->actingAs($user);

        $ids = $this->listedIds($this->openPanel());

        $this->assertContains($own->id, $ids);
        $this->assertNotContains($foreign->id, $ids);
    }

    public function test_panel_lists_all_media_with_view_all_permission(): void
    {
        $user = User::factory()->create();
        Permission::firstOrCreate(['name' => 'view_all_media']);
        $user->givePermissionTo('view_all_media');

        $own = Media::factory()->create(['creator_id' => $user->id, 'directory' => 'media']);
        $foreign = Media::factory()->create(['directory' => 'media']);

        $this->actingAs($user);

        $ids = $this->listedIds($this->openPanel());

        $this->assertContains($own->id, $ids);
        $this->assertContains($foreign->id, $ids);
    }
}
