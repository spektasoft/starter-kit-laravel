<?php

namespace Tests\Feature\Livewire\Api;

use App\Livewire\Api\ApiTokenManage;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Jetstream\Features;
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
        $testable->assertFormFieldExists('name');
    }

    public function test_api_tokens_can_be_created(): void
    {
        if (! Features::hasApiFeatures()) {
            $this->markTestSkipped('API support is not enabled.');
        }

        $this->actingAs($user = User::factory()->create());

        $testable = Livewire::test(ApiTokenManage::class);
        $testable->fillForm([
            'name' => 'Test Token',
            'permissions' => [
                'read',
                'update',
            ],
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

        /** @var ApiTokenManage */
        $component = Livewire::test(ApiTokenManage::class)->instance();
        $table = $component->getTable();

        /** @var Action */
        $action = collect($table->getActions())->first(function ($action) {
            if ($action instanceof ActionGroup) {
                return false;
            }

            return $action->getName() === 'permissions';
        });

        $action->formData([
            'abilities' => [
                'delete',
                'missing-permission',
            ],
        ])->call();

        /** @var PersonalAccessToken */
        $token = $user->fresh()?->tokens->first();
        $this->assertTrue($token->can('delete'));
        $this->assertFalse($token->can('read'));
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

        $user->tokens()->create([
            'name' => 'Test Token',
            'token' => Str::random(40),
            'abilities' => ['create', 'read'],
        ]);

        /** @var ApiTokenManage */
        $component = Livewire::test(ApiTokenManage::class)->instance();
        $table = $component->getTable();

        /** @var Action */
        $action = collect($table->getActions())->first(function ($action) {
            if ($action instanceof ActionGroup) {
                return false;
            }

            return $action->getName() === 'delete';
        });

        $action->call();

        /** @var Collection<int, PersonalAccessToken> */
        $tokens = $user->fresh()?->tokens;
        $this->assertCount(0, $tokens);
    }
}
