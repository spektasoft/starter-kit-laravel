<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArtisanControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_fail_if_config_is_disabled(): void
    {
        config(['api.artisan' => false]);
        $response = $this->postJson(route('api.v1.artisan.key.generate'), [
            'api_key' => config('api.key'),
        ]);

        $response->assertMethodNotAllowed();
    }

    public function test_should_fail_if_api_key_is_invalid(): void
    {
        config(['api.artisan' => true]);
        $response = $this->postJson(route('api.v1.artisan.key.generate'), [
            'api_key' => 'wrong-api-key',
        ]);

        $response->assertUnauthorized();
    }

    public function test_key_generate_should_success(): void
    {
        config(['api.artisan' => true]);
        $response = $this->postJson(route('api.v1.artisan.key.generate'), [
            'api_key' => config('api.key'),
        ]);

        $response->assertSuccessful();
    }

    public function test_migrate_should_success(): void
    {
        config(['api.artisan' => true]);
        $response = $this->postJson(route('api.v1.artisan.migrate'), [
            'api_key' => config('api.key'),
        ]);

        $response->assertSuccessful();
    }

    public function test_storage_link_should_success(): void
    {
        config(['api.artisan' => true]);
        $response = $this->postJson(route('api.v1.artisan.storage.link'), [
            'api_key' => config('api.key'),
        ]);

        $response->assertSuccessful();
    }

    public function test_throttle_should_limit_requests(): void
    {
        config(['api.artisan' => true]);

        /** @var int */
        $limit = config('api.limit_per_minute', 5);

        foreach (range(0, $limit) as $i) {
            $response = $this->postJson(route('api.v1.artisan.key.generate'), [
                'api_key' => config('api.key'),
            ]);

            if ($i < $limit) {
                $response->assertSuccessful();
            } else {
                $response->assertTooManyRequests();
            }
        }
    }
}
