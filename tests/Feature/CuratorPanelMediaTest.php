<?php

namespace Tests\Feature;

use App\Filament\Components\Modals\CuratorPanel;
use App\Models\Media;
use App\Models\Permission;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
     * @param  array<string, mixed>  $settings
     * @return Testable<CuratorPanel>
     */
    private function openPanel(array $settings = []): Testable
    {
        return Livewire::test(CuratorPanel::class, [
            'settings' => [
                'directory' => 'media',
                'diskName' => 'public',
                // The production plugin always passes Curator::getMinSize() and
                // Curator::getMaxSize(). Left null, the Uploader builds a min rule
                // with no value and rejects every file.
                'minSize' => 0,
                'maxSize' => 10240,
                'statePath' => 'data.image',
                'context' => 'test',
                ...$settings,
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

    /**
     * @param  Testable<CuratorPanel>  $component
     * @return array<int, string>
     */
    private function selectedIds(Testable $component): array
    {
        /** @var array<int, array{id: string}> $selected */
        $selected = $component->get('selected');

        return array_map(fn (array $item): string => $item['id'], $selected);
    }

    /**
     * @param  Testable<CuratorPanel>  $component
     * @return Testable<CuratorPanel>
     */
    private function uploadToPanel(Testable $component, string $filename): Testable
    {
        return $component->upload('panelData.files_to_add', [UploadedFile::fake()->image($filename, 40, 40)]);
    }

    /**
     * Matches the first `insert-media` dispatch. Livewire may deliver the
     * payload either positionally (params[0]) or as named params.
     *
     * @param  array<int, string>  $mediaIds
     */
    private function insertMediaPayload(string $statePath, string $context, array $mediaIds): Closure
    {
        return function (string $event, array $params) use ($statePath, $context, $mediaIds): bool {
            /** @var array{statePath?: string, context?: string, media?: array<int, array{id: string}>} $payload */
            $payload = array_key_exists('statePath', $params) ? $params : ($params[0] ?? []);

            return ($payload['statePath'] ?? null) === $statePath
                && ($payload['context'] ?? null) === $context
                && array_map(fn (array $item): string => $item['id'], $payload['media'] ?? []) === $mediaIds;
        };
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

    public function test_adding_files_stores_media_and_selects_it_without_inserting(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = $this->uploadToPanel($this->openPanel(['acceptedFileTypes' => ['image/jpeg', 'image/png', 'image/webp']]), 'panel-upload.jpg');
        $component->assertHasNoErrors();

        // Stage 1: the uploaded file must reach the panel form state.
        $this->assertNotEmpty($component->get('panelData.files_to_add'));

        // Stage 2: the action must save without validation errors.
        $component->callAction('addFiles');
        $component->assertHasNoErrors();
        $component->assertNotDispatched('insert-media');

        // The `curator-panel` global scope on Media matches any backtrace frame whose
        // path contains "CuratorPanel", which includes this test file. Bypass it.
        $media = Media::withoutGlobalScopes()->sole();

        $this->assertEquals($user->id, $media->creator_id);
        Storage::disk('public')->assertExists($media->path);
        $this->assertContains($media->id, $this->listedIds($component));
        $this->assertSame([$media->id], $this->selectedIds($component));
        $this->assertSame(1, Media::withoutGlobalScopes()->count());
    }

    public function test_add_insert_files_stores_media_and_dispatches_insert_media(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = $this->uploadToPanel($this->openPanel(['acceptedFileTypes' => ['image/jpeg', 'image/png', 'image/webp']]), 'panel-insert.jpg');
        $component->assertHasNoErrors();

        $this->assertNotEmpty($component->get('panelData.files_to_add'));

        $component->callAction('addInsertFiles');
        $component->assertHasNoErrors();

        $media = Media::withoutGlobalScopes()->sole();

        Storage::disk('public')->assertExists($media->path);
        $component->assertDispatched(
            'insert-media',
            $this->insertMediaPayload('data.image', 'test', [$media->id]),
        );
    }

    public function test_insert_media_dispatches_the_selected_media_with_panel_context(): void
    {
        $user = User::factory()->create();
        $media = Media::factory()->create(['creator_id' => $user->id, 'directory' => 'media']);

        $this->actingAs($user);

        $this->openPanel()
            ->set('selected', [$media->toArray()])
            ->callAction('insertMedia')
            ->assertDispatched(
                'insert-media',
                $this->insertMediaPayload('data.image', 'test', [$media->id]),
            );
    }
}
