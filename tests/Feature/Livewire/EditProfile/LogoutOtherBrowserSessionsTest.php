<?php

namespace Tests\Feature\Livewire\EditProfile;

use App\Livewire\EditProfile\LogoutOtherBrowserSessionsForm;
use App\Models\User;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Section;
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
        $testable->assertFormComponentExists('browser-sessions');
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

        /** @var Section */
        $section = $form->getComponent(function (Component $component) {
            return $component->getKey() === 'section';
        });

        /** @var Action */
        $action = collect($section->getFooterActions())->first(function (Action $action) {
            return $action->getName() === 'logout_other_browser_sessions';
        });

        $result = $action->formData([
            'current_password' => 'password',
        ])->call();

        $this->assertNull($result);
    }
}
