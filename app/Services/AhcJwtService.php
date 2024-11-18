<?php

namespace App\Services;

use Ahc\Jwt\JWT as AhcJwt;
use App\Contracts\Jwt;
use Exception;
use Illuminate\Support\Str;

class AhcJwtService implements Jwt
{
    /**
     * @var AhcJwt
     */
    private $jwt;

    public function __construct()
    {
        /** @var string */
        $key = config('app.key');
        if (Str::startsWith($key, 'base64:')) {
            $key = substr($key, 7);
            $secret = base64_decode($key);
            $this->jwt = new AhcJWT($secret);
        } else {
            throw new Exception('JWT creation failed, invalid APP_KEY.');
        }
    }

    public function encode($payload, $header = []): string
    {
        return $this->jwt->encode($payload);
    }

    public function decode(string $token, bool $verify = true)
    {
        /** @var array<string, mixed> */
        $payloads = $this->jwt->decode($token, $verify);

        return $payloads;
    }

    public function setTestTimestamp(?int $timestamp = null): void
    {
        $this->jwt->setTestTimestamp($timestamp);
    }
}
