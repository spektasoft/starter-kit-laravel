<?php

namespace Tests\Feature\Livewire\Auth;

use App\Livewire\Auth\ResetPassword;
use Livewire\Livewire;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    public function test_reset_password_can_be_rendered(): void
    {
        Livewire::test(ResetPassword::class)
            ->assertStatus(200);
    }
}
