<?php

namespace App\Data;

use Laravel\Jetstream\Agent;

class SessionData
{
    public function __construct(
        public Agent $agent,
        public string $ip_address,
        public bool $is_current_device,
        public string $last_active
    ) {}
}
