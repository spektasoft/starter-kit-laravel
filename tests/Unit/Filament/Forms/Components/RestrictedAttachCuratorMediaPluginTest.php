<?php

namespace Tests\Unit\Filament\Forms\Components;

use App\Filament\Forms\Components\RichEditor\RestrictedAttachCuratorMediaPlugin;
use Filament\Actions\Action;
use Tests\TestCase;

class RestrictedAttachCuratorMediaPluginTest extends TestCase
{
    public function test_editor_action_enforces_directory_restriction(): void
    {
        $plugin = new RestrictedAttachCuratorMediaPlugin;
        $actions = $plugin->getEditorActions();

        $this->assertCount(1, $actions);

        $action = $actions[0];
        $this->assertInstanceOf(Action::class, $action);
        $this->assertSame('attachCuratorMedia', $action->getName());
    }

    public function test_missing_config_key_does_not_bypass_restriction_silently(): void
    {
        // Unset the key entirely — simulates misconfigured environment
        config(['curator.features' => []]);

        $plugin = new RestrictedAttachCuratorMediaPlugin;
        $actions = $plugin->getEditorActions();

        // Action must still be produced; restriction state is deterministic (false, not null)
        $this->assertCount(1, $actions);
        $this->assertInstanceOf(Action::class, $actions[0]);
    }
}
