<?php

namespace Tests\Feature\Livewire\API;

use App\Livewire\Api\ApiTokenManage;
use Livewire\Livewire;
use Tests\TestCase;

class ApiTokenManageTest extends TestCase
{
    /** @test */
    public function test_API_token_manager_can_be_rendered(): void
    {
        Livewire::test(ApiTokenManage::class)
            ->assertStatus(200);
    }
}
