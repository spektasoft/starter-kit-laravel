<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Http\Livewire\UpdateProfileInformationForm;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileInformationTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_profile_information_is_available(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actingAs($user);

        /** @var UpdateProfileInformationForm */
        $component = Livewire::test(UpdateProfileInformationForm::class);

        $this->assertEquals($user->name, $component->state['name']);
        $this->assertEquals($user->email, $component->state['email']);
    }

    public function test_profile_information_can_be_updated(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(UpdateProfileInformationForm::class)
            ->set('state', ['name' => 'Test Name', 'email' => 'test@example.com'])
            ->call('updateProfileInformation');

        /** @var User */
        $freshUser = $user->fresh();
        $this->assertEquals('Test Name', $freshUser->name);
        $this->assertEquals('test@example.com', $freshUser->email);
    }
}
