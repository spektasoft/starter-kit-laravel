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
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(DeleteUserForm::class)
            ->assertStatus(200);
    }

    public function test_delete_user_form_renders_without_errors(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(DeleteUserForm::class)
            ->assertSuccessful();
    }

    public function test_account_deletion_warning_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(DeleteUserForm::class)
            ->assertSee('Once your account is deleted');
    }

    public function test_blocking_resources_warning_displays_when_blocked(): void
    {
        $user = User::factory()->create();

        // Create a page associated with the user to trigger the blocking condition
        $page = \App\Models\Page::factory()->create(['creator_id' => $user->id]);

        $this->actingAs($user);

        $component = Livewire::test(DeleteUserForm::class);
        $component->assertSee('Your account cannot be deleted yet');
        $component->assertSee('Pages (1)');
    }

    public function test_blocking_resources_warning_hidden_when_not_blocked(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(DeleteUserForm::class)
            ->assertDontSee('Your account cannot be deleted yet');
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
