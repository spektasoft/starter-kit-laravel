<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Media\Pages\ListMedia;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Media;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TableCustomizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        config(['auth.super_users' => [$this->admin->email]]);
        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    public function test_resource_list_tables_offer_exactly_twelve_and_twenty_four(): void
    {
        $tested = 0;
        foreach (Filament::getPanel('admin')->getResources() as $resource) {
            $this->assertTrue(is_subclass_of($resource, Resource::class), $resource);

            $registration = $resource::getPages()['index'] ?? null;
            if ($registration === null) {
                continue;
            }

            $page = $registration->getPage();
            if (! is_a($page, ListRecords::class, true)) {
                continue;
            }

            $component = Livewire::test($page)->assertSuccessful();
            $instance = $component->instance();
            $this->assertInstanceOf(ListRecords::class, $instance);
            $this->assertSame([12, 24], $instance->getTable()->getPaginationPageOptions(), $page);
            $tested++;
        }

        $this->assertGreaterThanOrEqual(7, $tested);
    }

    public function test_selecting_page_size_changes_the_number_of_visible_users(): void
    {
        User::factory()->count(25)->create();

        $component = Livewire::test(ListUsers::class)->assertSuccessful();
        $component->set('tableRecordsPerPage', 12);
        $instance = $component->instance();
        $this->assertInstanceOf(ListUsers::class, $instance);
        $records = $instance->getTableRecords();
        $this->assertInstanceOf(\Countable::class, $records);
        $this->assertCount(12, $records);

        $component->set('tableRecordsPerPage', 24);
        $instance = $component->instance();
        $this->assertInstanceOf(ListUsers::class, $instance);
        $records = $instance->getTableRecords();
        $this->assertInstanceOf(\Countable::class, $records);
        $this->assertCount(24, $records);
    }

    public function test_media_layout_switching_preserves_records_and_pagination(): void
    {
        config(['curator.resource.default_layout' => 'grid']);
        $media = Media::factory()->create(['creator_id' => $this->admin->id]);

        $component = Livewire::test(ListMedia::class)
            ->assertSuccessful()
            ->assertSet('layoutView', 'grid')
            ->assertCanSeeTableRecords([$media]);

        $instance = $component->instance();
        $this->assertInstanceOf(ListMedia::class, $instance);
        $this->assertSame([12, 24], $instance->getTable()->getPaginationPageOptions());
        $this->assertSame(['default' => 2, 'sm' => 3, 'md' => 3, 'lg' => 4], $instance->getTable()->getContentGrid());

        $component->call('changeLayoutView')
            ->assertSet('layoutView', 'list')
            ->assertCanSeeTableRecords([$media]);
        $instance = $component->instance();
        $this->assertInstanceOf(ListMedia::class, $instance);
        $this->assertNull($instance->getTable()->getContentGrid());
        $this->assertSame([12, 24], $instance->getTable()->getPaginationPageOptions());

        $component->call('changeLayoutView')
            ->assertSet('layoutView', 'grid')
            ->assertCanSeeTableRecords([$media]);
    }
}
