<?php

namespace Tests\Unit\PathGenerators;

use App\Models\User;
use App\PathGenerators\AuthenticatedUserPathGenerator;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class PathGeneratorDirectoryAlignmentTest extends TestCase
{
    public function test_path_matches_observer_directory_assumption(): void
    {
        config(['curator.default_directory' => 'media']);

        $user = User::factory()->make(['id' => 'user-ulid']);
        Auth::shouldReceive('user')->andReturn($user);

        $generator = new AuthenticatedUserPathGenerator;

        /** @var string|null $defaultDirectory */
        $defaultDirectory = config('curator.default_directory');
        $path = $generator->getPath($defaultDirectory);
        /** @var string */
        $authIdentifier = $user->getAuthIdentifier();

        // Observer constructs: config('curator.default_directory') . '/' . $newCreatorId
        $observerExpectedBase = $defaultDirectory.'/'.$authIdentifier;

        $this->assertSame($observerExpectedBase, $path);
    }
}
