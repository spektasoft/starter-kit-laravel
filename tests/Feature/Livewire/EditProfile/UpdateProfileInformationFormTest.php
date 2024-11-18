<?php

namespace Tests\Feature\Livewire\EditProfile;

use App\Livewire\EditProfile\UpdateProfileInformationForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UpdateProfileInformationFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_profile_information_form_can_be_rendered(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(UpdateProfileInformationForm::class)
            ->assertStatus(200);
    }

    public function test_current_profile_information_is_available(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actingAs($user);

        /** @var UpdateProfileInformationForm */
        $component = Livewire::test(UpdateProfileInformationForm::class)->instance();
        $form = $component->form;

        $this->assertEquals($user->name, $form->getState()['name']);
        $this->assertEquals($user->email, $form->getState()['email']);
    }

    public function test_profile_information_can_be_updated(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actingAs($user);

        $testable = Livewire::test(UpdateProfileInformationForm::class);
        $testable->fillForm(['name' => 'Test Name', 'email' => 'test@example.com']);
        $testable->call('updateProfileInformation');

        /** @var User */
        $freshUser = $user->fresh();
        $this->assertEquals('Test Name', $freshUser->name);
        $this->assertEquals('test@example.com', $freshUser->email);
    }
}
