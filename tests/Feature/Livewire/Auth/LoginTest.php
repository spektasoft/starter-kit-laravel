<?php

namespace Tests\Feature\Livewire\Auth;

use App\Livewire\Auth\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_can_be_rendered(): void
    {
        Livewire::test(Login::class)
            ->assertStatus(200);
    }

    public function test_login_has_form_and_fields(): void
    {
        Livewire::test(Login::class)
            ->assertFormExists()
            ->assertFormFieldExists('email')
            ->assertFormFieldExists('password')
            ->assertFormFieldExists('remember');
    }
}
