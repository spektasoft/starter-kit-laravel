<?php

namespace Tests\Feature;

use App\Filament\Components\Modals\CuratorPanel;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CuratorPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_curator_panel_renders_and_binds_explicitly_to_media_model(): void
    {
        // Attempt to render the component
        $component = Livewire::test(CuratorPanel::class)
            ->assertSuccessful();

        // Retrieve the form instance defined by the component
        $form = $component->instance()->getForm('form');

        // Verify the fix: The model should be the class string of App\Models\Media
        $this->assertEquals(Media::class, $form->getModel());
    }
}
