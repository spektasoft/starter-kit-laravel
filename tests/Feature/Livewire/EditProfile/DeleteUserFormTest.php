<?php

namespace Tests\Feature\Livewire\EditProfile;

use App\Livewire\EditProfile\DeleteUserForm;
use Livewire\Livewire;
use Tests\TestCase;

class DeleteUserFormTest extends TestCase
{
    /** @test */
    public function test_delete_user_form_can_be_rendered(): void
    {
        Livewire::test(DeleteUserForm::class)
            ->assertStatus(200);
    }
}
