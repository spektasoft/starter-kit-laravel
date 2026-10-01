<?php

namespace Tests\Feature\Models;

use App\Models\Export;
use App\Models\Import;
use App\Models\Media;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserResourcesTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{class-string<Export|Import|Media|Page>, string, string}> */
    public static function resourceTypes(): array
    {
        return [
            'pages' => [Page::class, 'page', 'pages'],
            'media' => [Media::class, 'media', 'media'],
            'exports' => [Export::class, 'export', 'exports'],
            'imports' => [Import::class, 'import', 'imports'],
        ];
    }

    /** @param class-string<Export|Import|Media|Page> $model */
    #[DataProvider('resourceTypes')]
    public function test_each_resource_type_blocks_only_its_creator(string $model, string $translation, string $resource): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        /** @var Factory<Export|Import|Media|Page> $factory */
        $factory = $model::factory();
        $factory->count(2)->create(['creator_id' => $user->id]);

        $this->assertTrue($user->isReferenced());
        $this->assertSame([
            [
                'label' => trans_choice($translation.'.resource.model_label', 2),
                'count' => 2,
                'route' => route('filament.admin.resources.'.$resource.'.index'),
            ],
        ], $user->getBlockingResources());
        $this->assertFalse($otherUser->isReferenced());
        $this->assertSame([], $otherUser->getBlockingResources());
    }

    public function test_all_resource_types_preserve_blocker_order_and_counts(): void
    {
        $user = User::factory()->create();
        Page::factory()->create(['creator_id' => $user->id]);
        Media::factory()->count(2)->create(['creator_id' => $user->id]);
        Export::factory()->count(3)->create(['creator_id' => $user->id]);
        Import::factory()->count(4)->create(['creator_id' => $user->id]);

        $expected = [];
        foreach ([['page', 'pages', 1], ['media', 'media', 2], ['export', 'exports', 3], ['import', 'imports', 4]] as [$translation, $resource, $count]) {
            $expected[] = [
                'label' => trans_choice($translation.'.resource.model_label', 2),
                'count' => $count,
                'route' => route('filament.admin.resources.'.$resource.'.index'),
            ];
        }

        $this->assertTrue($user->isReferenced());
        $this->assertSame($expected, $user->getBlockingResources());
    }
}
