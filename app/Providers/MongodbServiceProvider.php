<?php

namespace App\Providers;

use Jenssegers\Mongodb\Queue\MongoConnector;
use Jenssegers\Mongodb\Connection;
use Jenssegers\Mongodb\MongodbServiceProvider as MongodbServiceProviderBase;

class MongodbServiceProvider extends MongodbServiceProviderBase
{
    /**
     * Register the service provider.
     */
    public function register()
    {
        // Add database driver.
        $this->app->resolving('db', function ($db) {
            $db->extend('mongodb', function ($config, $name) {
                $config['name'] = $name;
                /**
                 * Dynamically create context if AWS DocumentDB options was provided
                 */
                if ($config['document_db_options']) {
                    $config['driver_options']['context'] = stream_context_create($config['document_db_options']);
                }
                return new Connection($config);
            });
        });

        // Add connector for queue support.
        $this->app->resolving('queue', function ($queue) {
            $queue->addConnector('mongodb', function () {
                return new MongoConnector($this->app['db']);
            });
        });
    }
}
