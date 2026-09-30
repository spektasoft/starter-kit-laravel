<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_configured_database_uses_a_supported_driver_and_persists_users(): void
    {
        $connection = (new User)->getConnection();

        $this->assertContains($connection->getDriverName(), ['sqlite', 'mysql']);
        $this->assertEmpty($connection->getConfig('url'));

        $user = User::factory()->create();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => $user->email,
        ]);
    }
}
