<?php

namespace App\Utils;

class Device
{
    public static bool $isAndroid = false;

    public static ?string $requestedWith = null;

    public static function setIsAndroid(bool $value): void
    {
        static::$isAndroid = $value;
    }

    public static function setRequestedWith(?string $value): void
    {
        static::$requestedWith = $value;
    }
}
