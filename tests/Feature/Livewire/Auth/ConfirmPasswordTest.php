<?php

namespace Tests\Feature\Livewire\Auth;

use App\Livewire\Auth\ConfirmPassword;
use Livewire\Livewire;
use Tests\TestCase;

class ConfirmPasswordTest extends TestCase
{
    /** @test */
    public function test_confirm_password_can_be_rendered(): void
    {
        Livewire::test(ConfirmPassword::class)
            ->assertStatus(200);
    }
}
