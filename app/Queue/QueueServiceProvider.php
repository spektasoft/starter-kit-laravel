<?php

namespace App\Queue;

use App\Queue\Connectors\DatabaseConnector;
use App\Queue\Failed\DatabaseUuidUlidFailedJobProvider;
use Illuminate\Queue\QueueServiceProvider as IlluminateQueueServiceProvider;

class QueueServiceProvider extends IlluminateQueueServiceProvider
{
    /**
     * Register the database queue connector.
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerDatabaseConnector($manager)
    {
        $manager->addConnector('database', function () {
            return new DatabaseConnector($this->app->make('db'));
        });
    }

    /**
     * Register the failed job services.
     *
     * @return void
     */
    protected function registerFailedJobServices()
    {
        /** @var array<string, string> */
        $config = $this->app->make('config')['queue.failed'];
        if (isset($config['driver']) && $config['driver'] === 'database-uuids-ulids') {
            $this->app->singleton('queue.failer', function ($app) {
                /** @var array<string, array<string, array<string, string>>> */
                $mApp = $app;
                $inner_config = $mApp['config']['queue.failed'];

                return new DatabaseUuidUlidFailedJobProvider(
                    $this->app->make('db'), $inner_config['database'], $inner_config['table']
                );
            });
        } else {
            parent::registerFailedJobServices();
        }
    }
}
