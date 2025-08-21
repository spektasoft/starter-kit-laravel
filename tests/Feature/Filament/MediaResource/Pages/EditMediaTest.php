<?php

namespace Tests\Feature\Filament\MediaResource\Pages;

use App\Filament\Resources\MediaResource\Pages\EditMedia;
use App\Models\Media; // Assuming Media model exists
use App\Models\User;
use Filament\Actions\Action;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase; // Assuming a base TestCase

class EditMediaTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_preview_action_url_is_correct(): void
    {
        $media = Media::factory()->create([
            'name' => 'test',
            'disk' => 'public',
            'directory' => 'media',
            'ext' => 'jpg',
            'path' => 'test.jpg',
            'creator_id' => $this->user->id,
        ]);

        // Test the Livewire component directly
        $component = Livewire::test(EditMedia::class, ['record' => $media->getRouteKey()]);
        $component->assertSuccessful();

        // Retrieve the actions from the Livewire component instance
        /** @var EditMedia */
        $instance = $component->instance();
        $actions = $instance->getHeaderActions();

        $previewAction = null;
        foreach ($actions as $action) {
            if ($action instanceof Action && $action->getName() === 'preview') {
                $previewAction = $action;
                break;
            }
        }

        $this->assertNotNull($previewAction, 'Preview action not found.');
        $this->assertEquals($media->url, $previewAction->getUrl(), 'Preview action URL does not match media URL.');
    }

    public function test_other_actions_are_unchanged(): void
    {
        // Create a media record
        $media = Media::factory()->create([
            'name' => 'test',
            'disk' => 'public',
            'directory' => 'media',
            'ext' => 'jpg',
            'path' => 'test.jpg',
            'creator_id' => $this->user->id,
        ]);

        // Get the actions from the Livewire component
        $component = Livewire::test(EditMedia::class, ['record' => $media->getRouteKey()]);
        $component->assertSuccessful();

        /** @var EditMedia */
        $instance = $component->instance();
        $actions = $instance->getHeaderActions();

        $foundOtherAction = false;
        foreach ($actions as $action) {
            // Assuming there\'s a \'delete\' action by default in Filament\'s Edit page
            if ($action instanceof Action && $action->getName() === 'delete') {
                $foundOtherAction = true;
                break;
            }
        }

        $this->assertTrue($foundOtherAction, 'Other actions (e.g., delete) were not found, indicating they might have been removed or altered.');
    }
}
