<?php

namespace Tests\Unit\PathGenerators;

use App\Models\User;
use App\PathGenerators\AuthenticatedUserPathGenerator;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthenticatedUserPathGeneratorTest extends TestCase
{
    public function test_returns_user_id_when_authenticated(): void
    {
        $user = User::factory()->make(['id' => 'user-123']);
        Auth::shouldReceive('user')->andReturn($user);

        $generator = new AuthenticatedUserPathGenerator;

        $this->assertSame('media/user-123', $generator->getPath('media'));
    }

    public function test_returns_bare_user_id_when_base_dir_is_null(): void
    {
        $user = User::factory()->make(['id' => 'user-123']);
        Auth::shouldReceive('user')->andReturn($user);

        $generator = new AuthenticatedUserPathGenerator;

        $this->assertSame('user-123', $generator->getPath(null));
    }

    public function test_throws_when_unauthenticated(): void
    {
        Auth::shouldReceive('user')->andReturn(null);

        $this->expectException(AuthenticationException::class);

        (new AuthenticatedUserPathGenerator)->getPath('media');
    }
}
