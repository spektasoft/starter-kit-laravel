<?php

namespace Tests\Feature\Livewire\Auth;

use App\Livewire\Auth\ForgotPassword;
use Livewire\Livewire;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    public function test_forgot_password_can_be_rendered(): void
    {
        Livewire::test(ForgotPassword::class)
            ->assertStatus(200);
    }
}
