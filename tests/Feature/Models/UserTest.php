<?php

namespace Tests\Feature\Models;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_blocking_resources_returns_empty_array_when_no_resources_exist(): void
    {
        // Create a user without any related resources
        $user = User::factory()->create();

        $blockingResources = $user->getBlockingResources();

        $this->assertEmpty($blockingResources);
    }

    public function test_get_blocking_resources_returns_array_with_page_resource(): void
    {
        // Create a user with one page
        $user = User::factory()->create();
        Page::factory()->create(['creator_id' => $user->id]);

        $blockingResources = $user->getBlockingResources();

        $this->assertCount(1, $blockingResources);

        $pageResource = $blockingResources[0];
        $this->assertEquals('Pages', $pageResource['label']);
        $this->assertEquals(1, $pageResource['count']);
        $this->assertStringContainsString(route('filament.admin.resources.pages.index'), $pageResource['route']);
    }

    public function test_get_blocking_resources_returns_multiple_resources_when_multiple_exist(): void
    {
        // Create a user with multiple types of resources
        $user = User::factory()->create();

        // Create multiple pages
        Page::factory()->count(2)->create(['creator_id' => $user->id]);

        // Create media files
        \App\Models\Media::factory()->count(3)->create(['creator_id' => $user->id]);

        // Create exports
        \App\Models\Export::factory()->count(1)->create(['creator_id' => $user->id]);

        $blockingResources = $user->getBlockingResources();

        $this->assertCount(3, $blockingResources);

        // Check that all expected resources are present
        $resourceLabels = array_column($blockingResources, 'label');
        $this->assertContains('Pages', $resourceLabels);
        $this->assertContains('Media', $resourceLabels);
        $this->assertContains('Exports', $resourceLabels);

        // Verify counts are correct
        /** @var array{label: string, count: int, route: string} */
        $pageResource = collect($blockingResources)->firstWhere('label', 'Pages');
        $this->assertEquals(2, $pageResource['count']);

        /** @var array{label: string, count: int, route: string} */
        $mediaResource = collect($blockingResources)->firstWhere('label', 'Media');
        $this->assertEquals(3, $mediaResource['count']);

        /** @var array{label: string, count: int, route: string} */
        $exportResource = collect($blockingResources)->firstWhere('label', 'Exports');
        $this->assertEquals(1, $exportResource['count']);
    }

    public function test_is_referenced_returns_true_when_resources_exist(): void
    {
        // Create a user with resources
        $user = User::factory()->create();
        Page::factory()->create(['creator_id' => $user->id]);

        $this->assertTrue($user->isReferenced());
    }

    public function test_is_referenced_returns_false_when_no_resources_exist(): void
    {
        // Create a user without any related resources
        $user = User::factory()->create();

        $this->assertFalse($user->isReferenced());
    }

    public function test_is_referenced_matches_get_blocking_resources_empty_status(): void
    {
        $user = User::factory()->create();

        // Test with no resources
        $this->assertFalse($user->isReferenced());
        $this->assertEmpty($user->getBlockingResources());

        // Add a resource
        Page::factory()->create(['creator_id' => $user->id]);

        // Test with resources
        $this->assertTrue($user->isReferenced());
        $this->assertNotEmpty($user->getBlockingResources());
    }
}
