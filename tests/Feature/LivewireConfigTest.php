<?php

namespace Tests\Feature;

use Tests\TestCase;

class LivewireConfigTest extends TestCase
{
    public function test_livewire_v4_configuration_keys_and_overrides(): void
    {
        $this->assertNull(config('livewire.layout'), 'v3 "layout" key must not exist.');
        $this->assertNull(config('livewire.lazy_placeholder'), 'v3 "lazy_placeholder" key must not exist.');

        $this->assertSame('components.layouts.app', config('livewire.component_layout'));
        $this->assertNull(config('livewire.component_placeholder'));
        $this->assertSame('class', config('livewire.make_command.type'));
        $this->assertTrue(config('livewire.smart_wire_keys'));

        $this->assertSame('App\\Livewire', config('livewire.class_namespace'));
        $this->assertSame(resource_path('views/livewire'), config('livewire.view_path'));
        $this->assertSame('tailwind', config('livewire.pagination_theme'));
        $this->assertTrue(config('livewire.navigate.show_progress_bar'));
        $this->assertSame('#ff4b0d', config('livewire.navigate.progress_bar_color'));
        $this->assertFalse(config('livewire.render_on_redirect'));
        $this->assertFalse(config('livewire.legacy_model_binding'));
        $this->assertTrue(config('livewire.inject_assets'));
        $this->assertTrue(config('livewire.inject_morph_markers'));
    }
}
