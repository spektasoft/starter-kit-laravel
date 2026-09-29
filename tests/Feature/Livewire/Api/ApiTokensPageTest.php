<?php

namespace Tests\Feature\Livewire\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Features;
use Tests\Concerns\AssertsLivewireEmbeds;
use Tests\TestCase;

class ApiTokensPageTest extends TestCase
{
    use AssertsLivewireEmbeds;
    use RefreshDatabase;

    public function test_api_tokens_page_renders_api_token_manage_component(): void
    {
        if (! Features::hasApiFeatures()) {
            $this->markTestSkipped('API support is not enabled.');
        }

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('api-tokens.index'))
            ->assertOk()
            ->assertSee(__('API Tokens'));

        $this->assertSeesLivewire(
            $response,
            'navigation-menu',
            'api.api-token-manager',
            'api.api-token-manage',
        );
    }
}
