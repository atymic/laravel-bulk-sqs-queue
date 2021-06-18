<?php

namespace Atymic\AsyncSqsQueue;

use Atymic\AsyncSqsQueue\Connector\SqsBulkConnector;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\ServiceProvider;

class AsyncSqsQueueServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /** @var QueueManager $queueManager */
        $queueManager = $this->app['queue'];

        $queueManager->addConnector('sqs-bulk', function () {
            return new SqsBulkConnector();
        });
    }
}
