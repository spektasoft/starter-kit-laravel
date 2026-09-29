<?php

namespace Tests\Feature\Livewire\EditProfile;

use App\Livewire\EditProfile\LogoutOtherBrowserSessionsForm;
use App\Models\Session;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Schemas\Components\Section;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;
use Livewire\Livewire;
use Tests\TestCase;

class LogoutOtherBrowserSessionsTest extends TestCase
{
    use InteractsWithSession;

    public function test_can_be_rendered(): void
    {
        Livewire::test(LogoutOtherBrowserSessionsForm::class)
            ->assertStatus(200);
    }

    public function test_form_and_components_exist(): void
    {
        $testable = Livewire::test(LogoutOtherBrowserSessionsForm::class);
        $testable->assertFormExists();
        $testable->assertFormComponentExists('section.browser-sessions');
    }

    public function test_lists_only_the_current_users_sessions_with_database_driver(): void
    {
        config(['session.driver' => 'database']);

        /** @var User */
        $user = User::factory()->create();
        /** @var User */
        $otherUser = User::factory()->create();
        $this->actingAs($user);

        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

        Session::withoutTimestamps(function () use ($user, $otherUser, $userAgent): void {
            Session::query()->forceCreate([
                'user_id' => $user->getKey(),
                'ip_address' => '203.0.113.7',
                'user_agent' => $userAgent,
                'payload' => '',
                'last_activity' => now()->subMinutes(5)->getTimestamp(),
            ]);

            Session::query()->forceCreate([
                'user_id' => $otherUser->getKey(),
                'ip_address' => '198.51.100.9',
                'user_agent' => $userAgent,
                'payload' => '',
                'last_activity' => now()->subMinutes(5)->getTimestamp(),
            ]);
        });

        $this->get(route('profile.show'))
            ->assertOk()
            ->assertSee('203.0.113.7')
            ->assertSee(__('Last active'))
            ->assertDontSee('198.51.100.9');
    }

    public function test_can_be_logged_out(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actingAs($user);

        /** @var LogoutOtherBrowserSessionsForm */
        $component = Livewire::test(LogoutOtherBrowserSessionsForm::class)
            ->instance();
        $form = $component->form;

        /** @var Section $section */
        $section = $form->getComponent('section');

        $footerActions = $section->getFooterActions();

        $action = null;
        foreach ($footerActions as $candidate) {
            if ($candidate instanceof Action && $candidate->getName() === 'logout_other_browser_sessions') {
                $action = $candidate;
                break;
            }
        }

        $this->assertNotNull($action, 'Action logout_other_browser_sessions was not found.');

        $result = $action->data([
            'current_password' => 'password',
        ])->call();

        $this->assertNull($result);
    }
}
