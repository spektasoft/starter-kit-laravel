<?php

namespace App\Contracts;

interface Jwt
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $header
     */
    public function encode($payload, $header = []): string;

    /**
     * @return array<string, mixed>
     */
    public function decode(string $token, bool $verify = true);

    public function setTestTimestamp(?int $timestamp = null): void;
}
