<?php

namespace Tests\Feature\Livewire\EditProfile;

use App\Livewire\EditProfile\LogoutOtherBrowserSessionsForm;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Schemas\Components\Section;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;
use Livewire\Livewire;
use Tests\TestCase;

class LogoutOtherBrowserSessionsTest extends TestCase
{
    use InteractsWithSession;

    public function test_can_be_rendered(): void
    {
        Livewire::test(LogoutOtherBrowserSessionsForm::class)
            ->assertStatus(200);
    }

    public function test_form_and_components_exist(): void
    {
        $testable = Livewire::test(LogoutOtherBrowserSessionsForm::class);
        $testable->assertFormExists();
        $testable->assertFormComponentExists('section.browser-sessions');
    }

    public function test_can_be_logged_out(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actingAs($user);

        /** @var LogoutOtherBrowserSessionsForm */
        $component = Livewire::test(LogoutOtherBrowserSessionsForm::class)
            ->instance();
        $form = $component->form;

        /** @var Section $section */
        $section = $form->getComponent('section');

        $footerActions = $section->getFooterActions();

        $action = null;
        foreach ($footerActions as $candidate) {
            if ($candidate instanceof Action && $candidate->getName() === 'logout_other_browser_sessions') {
                $action = $candidate;
                break;
            }
        }

        $this->assertNotNull($action, 'Action logout_other_browser_sessions was not found.');

        $result = $action->data([
            'current_password' => 'password',
        ])->call();

        $this->assertNull($result);
    }
}
