<?php

namespace Tests\Feature\Livewire\EditProfile;

use Laravel\Jetstream\Http\Livewire\UpdateProfileInformationForm;
use Livewire\Livewire;
use Tests\TestCase;

class UpdateProfileInformationFormTest extends TestCase
{
    /** @test */
    public function test_update_profile_information_form_can_be_rendered(): void
    {
        Livewire::test(UpdateProfileInformationForm::class)
            ->assertStatus(200);
    }
}
