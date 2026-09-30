<?php

namespace Tests\Feature\Filament\Forms\Components;

use App\Filament\Forms\Components\CuratorEnabledRichEditor;
use App\Filament\Forms\Components\RichEditor\RestrictedAttachCuratorMediaPlugin;
use App\Models\User;
use Awcodes\Curator\Components\Forms\RichEditor\AttachCuratorMediaPlugin;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CuratorEnabledRichEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_uses_restricted_plugin_not_base_plugin(): void
    {
        $component = CuratorEnabledRichEditor::make('content')
            ->container(Schema::make()->operation('test'));
        $plugins = $component->getPlugins();

        $pluginClasses = array_map(fn ($p) => get_class($p), $plugins);

        $this->assertContains(RestrictedAttachCuratorMediaPlugin::class, $pluginClasses);
        $this->assertNotContains(
            AttachCuratorMediaPlugin::class,
            $pluginClasses
        );
    }

    public function test_file_attachments_directory_uses_authenticated_user_path(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = CuratorEnabledRichEditor::make('content')
            ->container(Schema::make()->operation('test'));
        $directory = $component->getFileAttachmentsDirectory();
        /** @var string */
        $authIdentifier = $user->getAuthIdentifier();

        $this->assertSame('media/'.$authIdentifier, $directory);
    }

    public function test_file_attachments_disk_and_visibility_follow_curator_config(): void
    {
        config([
            'curator.default_disk' => 'curator-disk',
            'curator.default_visibility' => 'private',
            'filament.default_filesystem_disk' => 'other-disk',
        ]);

        $component = CuratorEnabledRichEditor::make('content')
            ->container(Schema::make()->operation('test'));

        $this->assertSame('curator-disk', $component->getFileAttachmentsDiskName());
        $this->assertSame('private', $component->getFileAttachmentsVisibility());
    }

    public function test_with_curator_macro_configures_the_same_editor_and_keeps_prior_settings(): void
    {
        // Intentional direct use: the macro is defined on the base RichEditor.
        // @phpstan-ignore-next-line
        $editor = RichEditor::make('content')
            ->required()
            ->container(Schema::make()->operation('test'));

        // @phpstan-ignore-next-line
        $result = $editor->withCurator();

        $this->assertSame($editor, $result);
        $this->assertTrue($result->isRequired());

        $pluginClasses = array_map(fn ($p) => get_class($p), $result->getPlugins());
        $this->assertContains(RestrictedAttachCuratorMediaPlugin::class, $pluginClasses);
    }
}
