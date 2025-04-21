<?php

namespace App\Services;

class DeviceService
{
    public bool $isAndroid = false;

    public ?string $requestedWith = null;

    public function setIsAndroid(bool $value): void
    {
        $this->isAndroid = $value;
    }

    public function setRequestedWith(?string $value): void
    {
        $this->requestedWith = $value;
    }
}
