<?php

namespace Tests\Feature\Livewire\Auth;

use App\Livewire\Auth\Login;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    public function test_login_can_be_rendered()
    {
        Livewire::test(Login::class)
            ->assertStatus(200);
    }
}
