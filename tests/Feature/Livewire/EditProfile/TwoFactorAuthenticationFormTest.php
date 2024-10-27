<?php

namespace Tests\Feature\Livewire\EditProfile;

use App\Livewire\EditProfile\TwoFactorAuthenticationForm;
use Livewire\Livewire;
use Tests\TestCase;

class TwoFactorAuthenticationFormTest extends TestCase
{
    /** @test */
    public function test_two_factor_authentication_form_can_be_rendered(): void
    {
        Livewire::test(TwoFactorAuthenticationForm::class)
            ->assertStatus(200);
    }
}
