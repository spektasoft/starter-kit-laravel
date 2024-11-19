<?php

namespace Tests\Feature\Livewire\EditProfile;

use App\Livewire\EditProfile\DeleteUserForm;
use App\Models\User;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Features;
use Livewire\Livewire;
use Tests\TestCase;

class DeleteUserFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_user_form_can_be_rendered(): void
    {
        Livewire::test(DeleteUserForm::class)
            ->assertStatus(200);
    }

    public function test_user_accounts_can_be_deleted(): void
    {
        if (! Features::hasAccountDeletionFeatures()) {
            $this->markTestSkipped('Account deletion is not enabled.');
        }

        /** @var User */
        $user = User::factory()->create();

        $this->actingAs($user);

        $action = $this->getDeleteAction();

        $action->formData([
            'current_password' => 'password',
        ])->call();

        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_before_account_can_be_deleted(): void
    {
        if (! Features::hasAccountDeletionFeatures()) {
            $this->markTestSkipped('Account deletion is not enabled.');
        }        /** @var User */
        $user = User::factory()->create();

        $this->actingAs($user);

        $action = $this->getDeleteAction();

        $this->assertThrows(fn () => $action->call());
    }

    private function getDeleteAction(): Action
    {
        /** @var DeleteUserForm */
        $component = Livewire::test(DeleteUserForm::class)->instance();
        /** @var Form */
        $form = $component->form;
        /** @var Section */
        $section = collect($form->getFlatComponents())->first(fn ($component) => $component instanceof Section);
        /** @var Action */
        $action = collect($section->getFooterActions())->first(fn ($action) => $action->getName() === 'delete_account');

        return $action;
    }
}
