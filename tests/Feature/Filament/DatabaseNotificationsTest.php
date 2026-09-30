<?php

namespace Tests\Feature\Filament;

use App\Filament\Livewire\DatabaseNotifications;
use App\Models\User;
use Filament\Enums\DatabaseNotificationsPosition;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DatabaseNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        $this->actingAs(User::factory()->create());
    }

    public function test_panel_notification_trigger_renders_with_and_without_unread_notifications(): void
    {
        $class = Filament::getDatabaseNotificationsLivewireComponent();
        $label = __('filament-panels::layout.actions.open_database_notifications.label');

        Livewire::test($class)->assertSuccessful()->assertSee($label);

        $user = User::auth();
        $this->assertInstanceOf(User::class, $user);

        Notification::make()
            ->title('Ticket 16 notification')
            ->sendToDatabase($user);

        $component = Livewire::test($class)
            ->assertSuccessful()
            ->assertSee('Ticket 16 notification')
            ->assertSee('database-notifications', escape: false);

        $instance = $component->instance();
        $this->assertInstanceOf(DatabaseNotifications::class, $instance);
        $this->assertSame(1, $instance->getUnreadNotificationsCount());

        $trigger = $instance->getTrigger();
        $this->assertInstanceOf(View::class, $trigger);
        $this->assertSame('filament.notifications.database-notifications-trigger', $trigger->name());
        $html = $trigger->with(['unreadNotificationsCount' => 1])->render();
        $this->assertStringContainsString('fi-topbar-database-notifications-btn', $html);
        $this->assertStringContainsString('fi-badge', $html);
        $this->assertStringContainsString('1', $html);
    }

    public function test_sidebar_position_retains_the_panel_sidebar_trigger(): void
    {
        $class = Filament::getDatabaseNotificationsLivewireComponent();
        $component = Livewire::test($class, ['position' => DatabaseNotificationsPosition::Sidebar]);

        $component->assertSuccessful();
        $instance = $component->instance();
        $this->assertInstanceOf(DatabaseNotifications::class, $instance);

        $trigger = $instance->getTrigger();
        $this->assertInstanceOf(View::class, $trigger);
        $this->assertSame(
            'filament-panels::components.sidebar.database-notifications-trigger',
            $trigger->name(),
        );
    }
}
