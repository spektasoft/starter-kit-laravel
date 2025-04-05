<?php

namespace App\Utils;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class ThrottleKey
{
    public static function generate(Request $request): string
    {
        /** @var string */
        $username = $request->input(Fortify::username());

        return Str::transliterate(Str::lower($username).'|'.$request->ip());
    }
}
