<?php

namespace Tests\Feature\Livewire\EditProfile;

use App\Livewire\EditProfile\LogoutOtherBrowserSessionsForm;
use Livewire\Livewire;
use Tests\TestCase;

class LogoutOtherBrowserSessionsTest extends TestCase
{
    public function test_logout_other_browser_sessions_form_can_be_rendered(): void
    {
        Livewire::test(LogoutOtherBrowserSessionsForm::class)
            ->assertStatus(200);
    }
}
