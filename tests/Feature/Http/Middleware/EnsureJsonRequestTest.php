<?php

namespace Tests\Feature\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Response;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EnsureJsonRequestTest extends TestCase
{
    public function test_valid_json_from_api_request(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $response = $this->getJson(route('api.v1.user'));

        $response->assertStatus(Response::HTTP_OK);
    }

    public function test_invalid_json_from_api_request(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $response = $this->get(route('api.v1.user'));

        $response->assertStatus(Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
    }

    public function test_valid_json_from_web_request(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actingAs($user);
        $response = $this->getJson(route('user'));

        $response->assertStatus(Response::HTTP_OK);
    }

    public function test_invalid_json_from_web_request(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actingAs($user);
        $response = $this->get(route('user'));

        $response->assertStatus(Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
    }
}
