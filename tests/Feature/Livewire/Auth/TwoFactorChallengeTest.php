<?php

namespace Tests\Feature\Livewire\Auth;

use App\Livewire\Auth\TwoFactorChallenge;
use Livewire\Livewire;
use Tests\TestCase;

class TwoFactorChallengeTest extends TestCase
{
    public function test_two_factor_challenge_can_be_rendered(): void
    {
        Livewire::test(TwoFactorChallenge::class)
            ->assertStatus(200);
    }
}
