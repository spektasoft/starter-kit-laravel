<?php

namespace App\Queue;

use Illuminate\Queue\DatabaseQueue as IlluminateDatabaseQueue;
use Illuminate\Support\Str;

class DatabaseQueue extends IlluminateDatabaseQueue
{
    /**
     * Create an array to insert for the given job.
     *
     * @param  string|null  $queue
     * @param  string  $payload
     * @param  int  $availableAt
     * @param  int  $attempts
     * @return array<string, int|string|null>
     */
    protected function buildDatabaseRecord($queue, $payload, $availableAt, $attempts = 0)
    {
        return [
            'id' => strtolower((string) Str::ulid()),
            'queue' => $queue,
            'payload' => $payload,
            'attempts' => $attempts,
            'reserved_at' => null,
            'available_at' => $availableAt,
            'created_at' => $this->currentTime(),
        ];
    }
}
