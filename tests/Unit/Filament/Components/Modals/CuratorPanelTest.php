<?php

namespace Tests\Unit\Filament\Components\Modals;

use App\Filament\Components\Modals\CuratorPanel;
use Tests\TestCase;

class CuratorPanelTest extends TestCase
{
    public function test_breadcrumbs_are_null_after_mount(): void
    {
        $panel = new CuratorPanel;

        // After mount, breadcrumbs must be null (package sentinel) not []
        $panel->mount();

        $this->assertNull($panel->breadcrumbs);
    }
}
