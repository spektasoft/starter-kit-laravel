<?php

namespace Tests\Feature\Livewire\EditProfile;

use App\Livewire\EditProfile\UpdatePasswordForm;
use Livewire\Livewire;
use Tests\TestCase;

class UpdatePasswordFormTest extends TestCase
{
    public function test_update_password_form_can_be_rendered(): void
    {
        Livewire::test(UpdatePasswordForm::class)
            ->assertStatus(200);
    }
}
