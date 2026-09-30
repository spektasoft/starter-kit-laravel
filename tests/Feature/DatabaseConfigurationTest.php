<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_suite_uses_sqlite_memory_storage_and_persists_users(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(
            ':memory:',
            config('database.connections.sqlite.database'),
        );
        $this->assertEmpty(config('database.connections.sqlite.url'));

        $user = User::factory()->create();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => $user->email,
        ]);
    }
}
