<?php

namespace Tests\Feature;

use App\Enums\Page\Status;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Features;
use Tests\TestCase;

class WebRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_access_home_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertViewIs('welcome');
    }

    public function test_unverified_user_cannot_access_home_page(): void
    {
        if (! Features::enabled(Features::emailVerification())) {
            $this->markTestSkipped('Email verification not enabled.');
        }

        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect('/email/verify');
    }

    public function test_verified_user_can_access_home_page(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertViewIs('welcome');
    }

    public function test_guest_can_access_a_page_php_route(): void
    {
        $page = Page::factory()->create(['status' => Status::Publish]);
        $this->get(route('pages.show', $page))
            ->assertOk();
    }

    public function test_unverified_user_cannot_access_a_page_php_route(): void
    {
        if (! Features::enabled(Features::emailVerification())) {
            $this->markTestSkipped('Email verification not enabled.');
        }

        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $page = Page::factory()->create(['status' => Status::Publish]);

        $this->actingAs($user)
            ->get(route('pages.show', $page))
            ->assertRedirect('/email/verify');
    }

    public function test_verified_user_can_access_a_page_php_route(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
        ]);

        $page = Page::factory()->create(['status' => Status::Publish]);

        $this->actingAs($user)
            ->get(route('pages.show', $page))
            ->assertOk();
    }
}
