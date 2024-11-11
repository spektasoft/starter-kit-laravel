<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

class PermissionData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        #[MapName(SnakeCaseMapper::class)]
        public string $guardName,
    ) {}
}
