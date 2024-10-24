<?php

namespace Tests\Feature\Livewire\Auth;

use App\Livewire\Auth\Register;
use Livewire\Livewire;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    public function test_register_can_be_rendered(): void
    {
        Livewire::test(Register::class)
            ->assertStatus(200);
    }
}
