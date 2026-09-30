<?php

namespace Tests\Feature;

use App\Filament\Components\Modals\CuratorPanel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CuratorPanelBreadcrumbsTest extends TestCase
{
    use RefreshDatabase;

    public function test_breadcrumbs_stay_null_after_a_directory_change(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CuratorPanel::class, [
            'settings' => [
                'directory' => 'media',
                'statePath' => 'data.image',
                'context' => 'test',
            ],
        ])
            ->assertSet('breadcrumbs', null)
            ->call('handleDirectoryChange', 'media')
            ->assertSet('breadcrumbs', null);
    }
}
