<?php

namespace Tests\Feature\Filament\Resources;

use App\Filament\Resources\Media\MediaResource;
use App\Filament\Resources\Media\Pages\CreateMedia;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CreateMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_page_is_bound_to_the_app_media_resource(): void
    {
        $this->assertSame(MediaResource::class, CreateMedia::getResource());
        $this->assertSame(CreateMedia::class, MediaResource::getPages()['create']->getPage());
    }

    public function test_create_route_renders_for_authenticated_user(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(MediaResource::getUrl('create'))->assertSuccessful();
    }

    public function test_uploading_a_file_creates_media_record_and_stores_the_file(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(CreateMedia::class)
            ->fillForm(['file' => UploadedFile::fake()->image('holiday.jpg', 40, 40)])
            ->call('create')
            ->assertHasNoFormErrors();

        $media = Media::query()->sole();

        $this->assertEquals($user->id, $media->creator_id);
        $this->assertSame('holiday', $media->title);
        Storage::disk('public')->assertExists($media->path);
    }
}
