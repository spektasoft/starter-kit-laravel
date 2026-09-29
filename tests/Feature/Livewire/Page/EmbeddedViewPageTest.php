<?php

namespace Tests\Feature\Livewire\Page;

use App\Enums\Page\Status;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Jetstream;
use Tests\Concerns\AssertsLivewireEmbeds;
use Tests\TestCase;

class EmbeddedViewPageTest extends TestCase
{
    use AssertsLivewireEmbeds;
    use RefreshDatabase;

    public function test_terms_of_service_embeds_view_page(): void
    {
        if (! Jetstream::hasTermsAndPrivacyPolicyFeature()) {
            $this->markTestSkipped('Terms and privacy policy feature is not enabled.');
        }

        $page = Page::factory()->create([
            'status' => Status::Publish,
            'title' => ['en' => 'Smoke Terms Title'],
            'content' => ['en' => 'Smoke terms content.'],
        ]);
        config(['page.terms' => $page->id]);

        $response = $this->get('/terms-of-service')
            ->assertOk()
            ->assertSee('Smoke Terms Title')
            ->assertSee('Smoke terms content.');

        $this->assertSeesLivewire($response, 'page.view-page', 'navigation-menu');
    }

    public function test_privacy_policy_embeds_view_page(): void
    {
        if (! Jetstream::hasTermsAndPrivacyPolicyFeature()) {
            $this->markTestSkipped('Terms and privacy policy feature is not enabled.');
        }

        $page = Page::factory()->create([
            'status' => Status::Publish,
            'title' => ['en' => 'Smoke Privacy Title'],
            'content' => ['en' => 'Smoke privacy content.'],
        ]);
        config(['page.privacy' => $page->id]);

        $response = $this->get('/privacy-policy')
            ->assertOk()
            ->assertSee('Smoke Privacy Title')
            ->assertSee('Smoke privacy content.');

        $this->assertSeesLivewire($response, 'page.view-page', 'navigation-menu');
    }
}
