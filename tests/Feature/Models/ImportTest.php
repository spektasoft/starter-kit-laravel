<?php

namespace Tests\Feature\Models;

use App\Models\Import;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_creator_id_is_set_on_creation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $import = Import::factory()->create();

        $this->assertEquals($user->id, $import->creator_id);
    }

    public function test_create_import_throws_exception_when_not_authenticated(): void
    {
        $this->expectException(\RuntimeException::class);
        Import::factory()->create();
    }
}
