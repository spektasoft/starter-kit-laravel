<?php

namespace Tests\Feature\Filament\Forms\Components;

use App\Filament\Components\Modals\CuratorPanel;
use App\Filament\Forms\Components\CuratorEnabledRichEditor;
use App\Filament\Forms\Components\RichEditor\RestrictedAttachCuratorMediaPlugin;
use App\Models\User;
use Awcodes\Curator\Facades\Curator;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RichEditorPanelSettingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function settingsForNewEditor(): array
    {
        $this->actingAs(User::factory()->create());

        $editor = CuratorEnabledRichEditor::make('content')
            ->container(Schema::make()->operation('test'));

        return (new RestrictedAttachCuratorMediaPlugin)->getPanelSettings($editor);
    }

    public function test_accepted_file_types_default_to_the_rich_editor_image_types(): void
    {
        $settings = $this->settingsForNewEditor();

        $this->assertSame(
            ['image/png', 'image/jpeg', 'image/gif', 'image/webp'],
            $settings['acceptedFileTypes']
        );
    }

    public function test_accepted_file_types_fall_back_to_curator_defaults_when_cleared(): void
    {
        $this->actingAs(User::factory()->create());

        $editor = CuratorEnabledRichEditor::make('content')
            ->fileAttachmentsAcceptedFileTypes(fn (): ?array => null)
            ->container(Schema::make()->operation('test'));

        $settings = (new RestrictedAttachCuratorMediaPlugin)->getPanelSettings($editor);

        $this->assertSame(Curator::getAcceptedFileTypes(), $settings['acceptedFileTypes']);

        Livewire::test(CuratorPanel::class, ['settings' => $settings])
            ->assertSuccessful();
    }

    public function test_curator_panel_mounts_with_the_rich_editor_settings(): void
    {
        $settings = $this->settingsForNewEditor();

        Livewire::test(CuratorPanel::class, ['settings' => $settings])
            ->assertSuccessful()
            ->assertSet('context', 'richEditor')
            ->assertSet('statePath', $settings['statePath']);
    }
}
