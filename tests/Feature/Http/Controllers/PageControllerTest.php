<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\Page\Status;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_terms_of_service_returns_200_when_configured_and_published(): void
    {
        $page = Page::factory()->create([
            'status' => Status::Publish,
        ]);

        config(['page.terms' => $page->id]);

        $response = $this->get('/terms-of-service');

        $response->assertStatus(200);
        $response->assertViewIs('terms-of-service');
        $response->assertViewHas('record', $page);
    }

    public function test_terms_of_service_returns_404_when_not_configured(): void
    {
        config(['page.terms' => null]);

        $response = $this->get('/terms-of-service');

        $response->assertStatus(404);
    }

    public function test_terms_of_service_returns_404_when_page_is_not_published(): void
    {
        $page = Page::factory()->create([
            'status' => Status::Draft,
        ]);

        config(['page.terms' => $page->id]);

        $response = $this->get('/terms-of-service');

        $response->assertStatus(404);
    }

    public function test_privacy_policy_returns_200_when_configured_and_published(): void
    {
        $page = Page::factory()->create([
            'status' => Status::Publish,
        ]);

        config(['page.privacy' => $page->id]);

        $response = $this->get('/privacy-policy');

        $response->assertStatus(200);
        $response->assertViewIs('privacy-policy');
        $response->assertViewHas('record', $page);
    }

    public function test_privacy_policy_returns_404_when_not_configured(): void
    {
        config(['page.privacy' => null]);

        $response = $this->get('/privacy-policy');

        $response->assertStatus(404);
    }

    public function test_privacy_policy_returns_404_when_page_is_not_published(): void
    {
        $page = Page::factory()->create([
            'status' => Status::Draft,
        ]);

        config(['page.privacy' => $page->id]);

        $response = $this->get('/privacy-policy');

        $response->assertStatus(404);
    }

    public function test_fallback_route_returns_404_and_correct_view(): void
    {
        $response = $this->get('/this-route-does-not-exist');

        $response->assertStatus(404);
        $response->assertViewIs('errors.404');
    }
}
