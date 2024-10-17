<?php

namespace App\Queue\Failed;

use Illuminate\Queue\Failed\DatabaseUuidFailedJobProvider;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

class DatabaseUuidUlidFailedJobProvider extends DatabaseUuidFailedJobProvider
{
    /**
     * Log a failed job into storage.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  string  $payload
     * @param  \Throwable  $exception
     * @return string|null
     */
    public function log($connection, $queue, $payload, $exception)
    {
        /** @var array<string, string> */
        $json_payload = json_decode($payload, true);
        $uuid = $json_payload['uuid'];
        /** @var string */
        $exception = mb_convert_encoding($exception, 'UTF-8');

        $this->getTable()->insert([
            'id' => strtolower((string) Str::ulid()),
            'uuid' => $uuid,
            'connection' => $connection,
            'queue' => $queue,
            'payload' => $payload,
            'exception' => $exception,
            'failed_at' => Date::now(),
        ]);

        return $uuid;
    }
}
