<?php

declare(strict_types=1);

namespace App\PathGenerators;

use Awcodes\Curator\PathGenerators\Contracts\PathGenerator;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

class AuthenticatedUserPathGenerator implements PathGenerator
{
    /**
     * @throws AuthenticationException
     */
    public function getPath(?string $baseDir = null): string
    {
        $user = Auth::user();

        if (is_null($user)) {
            throw new AuthenticationException(
                'UserPathGenerator requires an authenticated user.'
            );
        }

        $prefix = in_array($baseDir, [null, '', '0'], true) ? '' : $baseDir.'/';
        /** @var string */
        $authIdentifier = $user->getAuthIdentifier();

        return $prefix.$authIdentifier;
    }
}
