<?php

namespace Tests\Feature\Livewire\Auth;

use App\Livewire\Auth\VerifyEmail;
use Livewire\Livewire;
use Tests\TestCase;

class VerifyEmailTest extends TestCase
{
    public function test_verify_email_can_be_rendered(): void
    {
        Livewire::test(VerifyEmail::class)
            ->assertStatus(200);
    }
}
