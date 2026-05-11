<?php

namespace Tests\Feature\Livewire\Api;

use App\Livewire\Api\ApiTokenManage;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Jetstream\Features;
use Laravel\Jetstream\Jetstream;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class ApiTokenManageTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_token_manage_cannot_be_rendered_by_guest(): void
    {
        if (! Features::hasApiFeatures()) {
            $this->markTestSkipped('API support is not enabled.');
        }

        Livewire::test(ApiTokenManage::class)
            ->assertStatus(403);
    }

    public function test_api_token_manage_can_be_rendered_after_login(): void
    {
        if (! Features::hasApiFeatures()) {
            $this->markTestSkipped('API support is not enabled.');
        }

        /** @var User */
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(ApiTokenManage::class)
            ->assertStatus(200);
    }

    public function test_api_token_manage_has_form_and_fields(): void
    {
        if (! Features::hasApiFeatures()) {
            $this->markTestSkipped('API support is not enabled.');
        }

        /** @var User */
        $user = User::factory()->create();
        $this->actingAs($user);

        $testable = Livewire::test(ApiTokenManage::class);
        $testable->assertFormExists();
        $testable->assertFormFieldExists('name', 'form');
    }

    public function test_api_tokens_can_be_created(): void
    {
        if (! Features::hasApiFeatures()) {
            $this->markTestSkipped('API support is not enabled.');
        }

        $this->actingAs($user = User::factory()->create());

        $testable = Livewire::test(ApiTokenManage::class);
        $testable->set('name', 'Test Token');
        $testable->set('permissions', [
            'read',
            'update',
        ]);
        $testable->call('createApiToken');

        /** @var Collection<int, PersonalAccessToken> */
        $tokens = $user->fresh()?->tokens;
        $this->assertCount(1, $tokens);

        /** @var PersonalAccessToken */
        $token = $tokens->first();
        $this->assertEquals('Test Token', $token->name);
        $this->assertTrue($token->can('read'));
        $this->assertFalse($token->can('delete'));
    }

    public function test_api_token_permissions_can_be_updated(): void
    {
        if (! Features::hasApiFeatures()) {
            $this->markTestSkipped('API support is not enabled.');
        }

        /** @var User */
        $user = User::factory()->create();

        $this->actingAs($user);

        $token = $user->tokens()->create([
            'name' => 'Test Token',
            'token' => Str::random(40),
            'abilities' => ['create', 'read'],
        ]);

        Livewire::test(ApiTokenManage::class)
            ->callAction(TestAction::make('permissions')->table($token), [
                'abilities' => [
                    'delete',
                ],
            ]);

        /** @var PersonalAccessToken */
        $token = $user->fresh()?->tokens->first();
        $this->assertTrue($token->can('delete'));
        $this->assertFalse($token->can('create'));
        $this->assertFalse($token->can('missing-permission'));
    }

    public function test_api_tokens_can_be_deleted(): void
    {
        if (! Features::hasApiFeatures()) {
            $this->markTestSkipped('API support is not enabled.');
        }

        /** @var User */
        $user = User::factory()->create();

        $this->actingAs($user);

        $token = $user->tokens()->create([
            'name' => 'Test Token',
            'token' => Str::random(40),
            'abilities' => ['create', 'read'],
        ]);

        Livewire::test(ApiTokenManage::class)
            ->callTableAction('delete', $token);

        /** @var Collection<int, PersonalAccessToken> */
        $tokens = $user->fresh()?->tokens;
        $this->assertCount(0, $tokens);
    }

    public function test_permission_options_are_displayed_in_create_and_edit_forms(): void
    {
        if (! Features::hasApiFeatures()) {
            $this->markTestSkipped('API support is not enabled.');
        }

        /** @var User */
        $user = User::factory()->create();
        $this->actingAs($user);

        /** @var list<string> $permissions */
        $permissions = array_values(Jetstream::$permissions);

        $testable = Livewire::test(ApiTokenManage::class);

        // Check create form
        $testable->assertSeeInOrder($permissions);

        // Create a token to test the edit form
        $token = $user->tokens()->create([
            'name' => 'Test Token',
            'token' => Str::random(40),
            'abilities' => ['create', 'read'],
        ]);

        $testable->mountTableAction('permissions', $token);

        // Check edit form
        $testable->assertSeeInOrder($permissions);
    }

    public function test_can_close_token_display_modal(): void
    {
        if (! Features::hasApiFeatures()) {
            $this->markTestSkipped('API support is not enabled.');
        }

        /** @var User */
        $user = User::factory()->create();
        $this->actingAs($user);

        $testable = Livewire::test(ApiTokenManage::class);

        $testable->call('closeModalTokenDisplay');

        $testable->assertDispatched('close-modal');
    }

    public function test_api_tokens_can_be_bulk_deleted(): void
    {
        if (! Features::hasApiFeatures()) {
            $this->markTestSkipped('API support is not enabled.');
        }

        /** @var User */
        $user = User::factory()->create();

        $this->actingAs($user);

        $user->tokens()->create([
            'name' => 'Test Token 1',
            'token' => Str::random(40),
            'abilities' => ['create', 'read'],
        ]);

        $user->tokens()->create([
            'name' => 'Test Token 2',
            'token' => Str::random(40),
            'abilities' => ['create', 'read'],
        ]);

        /** @var Testable */
        $testable = Livewire::test(ApiTokenManage::class);
        $testable->selectTableRecords($user->tokens->pluck('id')->toArray())
            ->callAction(TestAction::make('delete')->table()->bulk());

        /** @var Collection<int, PersonalAccessToken> */
        $tokens = $user->fresh()?->tokens;
        $this->assertCount(0, $tokens);
    }

    public function test_api_token_is_displayed_after_creation(): void
    {
        if (! Features::hasApiFeatures()) {
            $this->markTestSkipped('API support is not enabled.');
        }

        $this->actingAs(User::factory()->create());

        $testable = Livewire::test(ApiTokenManage::class);
        $testable->set('name', 'Test Token');
        $testable->set('permissions', [
            'read',
            'update',
        ]);
        $testable->call('createApiToken');

        /** @var ApiTokenManage */
        $component = $testable->instance();
        $this->assertNotNull($component->plainTextToken);
        $testable->assertDispatched('open-modal', id: 'modal-token-display');
    }

    public function test_name_field_is_required_during_token_creation(): void
    {
        if (! Features::hasApiFeatures()) {
            $this->markTestSkipped('API support is not enabled.');
        }

        $this->actingAs($user = User::factory()->create());

        $testable = Livewire::test(ApiTokenManage::class);
        $testable->set('name', '');
        $testable->set('permissions', [
            'read',
            'update',
        ]);
        $testable->call('createApiToken');

        $testable->assertHasErrors(['name' => 'required']);
    }
}
