<?php
declare(strict_types=1);

namespace Atymic\AsyncSqsQueue\Connector;

use Atymic\AsyncSqsQueue\SqsBulkQueue;
use Aws\Sqs\SqsClient;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\Connectors\SqsConnector as IlluminateSqsConnector;
use Illuminate\Support\Arr;

class SqsBulkConnector extends IlluminateSqsConnector
{
    public function connect(array $config): Queue
    {
        $concurrency = $config['concurrency'] ?? null;
        unset($config['concurrency']);

        $config = $this->getDefaultConfiguration($config);

        if ($config['key'] && $config['secret']) {
            $config['credentials'] = Arr::only($config, ['key', 'secret', 'token']);
        }

        $queue = new SqsBulkQueue(
            new SqsClient($config),
            $config['queue'],
            $config['prefix'] ?? '',
            $config['suffix'] ?? '',
            $config['after_commit'] ?? null
        );

        if ($concurrency !== null) {
            $queue->setConcurrency($concurrency);
        }

        return $queue;
    }
}
