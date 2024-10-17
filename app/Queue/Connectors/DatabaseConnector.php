<?php

namespace App\Queue\Connectors;

use App\Queue\DatabaseQueue;
use Illuminate\Database\Connection;
use Illuminate\Queue\Connectors\DatabaseConnector as IlluminateDatabaseConnector;

class DatabaseConnector extends IlluminateDatabaseConnector
{
    /**
     * Establish a queue connection.
     *
     * @param  array<string, string|int|bool|null>  $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        $connection = $this->connections->connection(strval($config['connection'] ?? null));

        if ($connection instanceof Connection) {
            return new DatabaseQueue(
                $connection,
                strval($config['table']),
                strval($config['queue']),
                intval($config['retry_after'] ?? 60),
                boolval($config['after_commit'] ?? null)
            );
        }

        throw new \Exception('DatabaseConnector: connection error');
    }
}
