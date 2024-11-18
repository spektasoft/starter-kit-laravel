<?php

namespace App\Data;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

class UserData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        #[MapName(SnakeCaseMapper::class)]
        public CarbonImmutable|Optional $emailVerifiedAt,
        #[MapName(SnakeCaseMapper::class)]
        public CarbonImmutable $createdAt,
        #[MapName(SnakeCaseMapper::class)]
        public CarbonImmutable $updatedAt,
    ) {}
}
