<?php

namespace Tests\Feature\Livewire\Auth;

use App\Livewire\Auth\VerifyEmail;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class VerifyEmailTest extends TestCase
{
    public function test_verify_email_can_be_rendered(): void
    {
        /** @var User */
        $user = User::factory()->withPersonalTeam()->unverified()->create();
        $this->actingAs($user);

        Livewire::test(VerifyEmail::class)
            ->assertStatus(200);
    }
}
