<?php

namespace Tests\Feature;

use App\Filament\Components\Modals\CuratorPanel;
use Livewire\Livewire;
use Tests\TestCase;

class CuratorWiringTest extends TestCase
{
    public function test_curator_panel_alias_resolves_to_the_application_subclass(): void
    {
        $this->assertInstanceOf(CuratorPanel::class, Livewire::new('curator-panel'));
    }

    public function test_public_layout_renders_the_curator_modal_for_guests(): void
    {
        $this->get('/a-route-that-does-not-exist')
            ->assertNotFound()
            ->assertSee('curator-panel', false);
    }
}
