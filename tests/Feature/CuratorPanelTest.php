<?php

namespace Tests\Feature;

use App\Filament\Components\Modals\CuratorPanel;
use App\Models\Media;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CuratorPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_curator_panel_renders_and_binds_explicitly_to_media_model(): void
    {
        // Attempt to render the component
        $component = Livewire::test(CuratorPanel::class);
        $component->assertSuccessful();

        /** @var CuratorPanel $instance */
        $instance = $component->instance();

        // Retrieve the schema instance defined by the component
        $form = $instance->getSchema('form');
        $this->assertInstanceOf(Schema::class, $form, 'The curator panel schema should not be null.');

        // Verify the fix: The model should be the class string of App\Models\Media
        $this->assertEquals(Media::class, $form->getModel());
    }
}
