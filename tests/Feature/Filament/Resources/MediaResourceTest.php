<?php

namespace Tests\Feature\Filament\Resources;

use App\Filament\Resources\MediaResource;
use App\Models\Media;
use App\Models\Permission;
use App\Models\User;
use Awcodes\Curator\Resources\MediaResource\CreateMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class MediaResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_page_can_be_rendered(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(MediaResource::getUrl('index'))->assertSuccessful();
    }

    public function test_table_columns_are_displayed_with_owned_media(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $media = Media::factory()->create(['creator_id' => $user->id]);

        $this->get(MediaResource::getUrl('index'))
            ->assertSee($media->pretty_name)
            ->assertSee($media->size_for_humans);
    }

    public function test_table_columns_are_not_displayed_with_unowned_media(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $media = Media::factory()->create();

        $this->get(MediaResource::getUrl('index'))
            ->assertDontSee($media->pretty_name)
            ->assertDontSee($media->size_for_humans);
    }

    public function test_table_columns_are_displayed_with_view_all_permission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        // Assign 'view_all_media' permission to the user
        Permission::firstOrCreate(['name' => 'view_all_media']);
        $user->givePermissionTo('view_all_media');

        $media = Media::factory()->create();

        $this->get(MediaResource::getUrl('index'))
            ->assertSee($media->pretty_name)
            ->assertSee($media->size_for_humans);
    }

    public function test_creator_id_is_required_when_view_all_permission_is_granted(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Assign 'view_all_media' permission to the user
        Permission::firstOrCreate(['name' => 'view_all_media']);
        $user->givePermissionTo('view_all_media');

        // Assuming 'name' and 'file' are required fields for Media creation
        // and that 'creator_id' is the only field we are intentionally null for validation.
        $livewire = Livewire::test(CreateMedia::class);
        $livewire->fillForm([
            'name' => 'Test Media Name',
            'file' => UploadedFile::fake()->image('test_image.jpg'),
            'creator_id' => null,
        ]);
        $livewire->call('create');
        $livewire->assertHasErrors(['data.creator_id']);
    }
}
