<?php

namespace Atymic\AsyncSqsQueue\Tests;

use Atymic\AsyncSqsQueue\AsyncSqsQueueServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Atymic\\AsyncSqsQueue\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            AsyncSqsQueueServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        include_once __DIR__.'/../database/migrations/create_laravel-bulk-sqs-queue_table.php.stub';
        (new \CreatePackageTable())->up();
        */
    }
}
