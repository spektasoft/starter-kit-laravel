<?php

namespace Tests\Feature\Filament\Forms\Components;

use App\Filament\Forms\Components\CuratorEnabledRichEditor;
use App\Filament\Forms\Components\RichEditor\RestrictedAttachCuratorMediaPlugin;
use App\Models\User;
use Awcodes\Curator\Components\Forms\RichEditor\AttachCuratorMediaPlugin;
use Filament\Schemas\Schema;
use Tests\TestCase;

class CuratorEnabledRichEditorTest extends TestCase
{
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
}
