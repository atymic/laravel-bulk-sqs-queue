<?php
declare(strict_types=1);

namespace Atymic\AsyncSqsQueue;

use Atymic\AsyncSqsQueue\Exception\BulkSqsDispatchFailed;
use Aws\Result;
use Generator;
use GuzzleHttp\Promise\Each;
use GuzzleHttp\Promise\Promise;
use Illuminate\Queue\SqsQueue as IlluminateSqsQueue;
use Illuminate\Support\Str;

class SqsBulkQueue extends IlluminateSqsQueue
{
    protected int $concurrency = 10;

    /** @var int SQS allows up to 10 messages per batch */
    protected const BATCH_LIMIT = 10;
    /** @var int Limit batch sizes to 200kb, to account for request overhead (msg ids, http, etc) */
    protected const BATCH_SIZE_LIMIT = 200 * 1024;

    public function bulk($jobs, $data = '', $queue = null): void
    {
        $responses = collect();

        $promise = Each::ofLimit(
            $this->batchGenerator($jobs, $data, $queue),
            $this->concurrency,
            fn (Result $res) => $responses->push($res)
        );

        $promise->wait();

        $failed = $responses
            ->filter(fn (Result $res) => count($res['Failed'] ?? []))
            ->flatten(1);

        if ($failed->isNotEmpty()) {
            throw BulkSqsDispatchFailed::withFailureResponses($failed);
        }
    }

    protected function batchGenerator($jobs, $data = '', $queue = null): Generator
    {
        $queue = $queue ?: $this->default;
        
        $batchPayloads = [];
        $batchBytes = 0;

        foreach ($jobs as $job) {
            $payload = $this->createPayload($job, $queue ?: $this->default, $data);

            $batchPayloads[] = $payload;
            $batchBytes += strlen($payload);

            if ($batchBytes >= self::BATCH_SIZE_LIMIT || count($batchPayloads) >= self::BATCH_LIMIT) {
                yield $this->dispatchBatchAsync($queue, $batchPayloads);
                $batchPayloads = [];
                $batchBytes = 0;
            }
        }

        if (count($batchPayloads)) {
            yield $this->dispatchBatchAsync($queue, $batchPayloads);
        }
    }

    protected function dispatchBatchAsync(string $queue, array $payloads): Promise
    {
        return $this->sqs->sendMessageBatchAsync([
            'QueueUrl' => $this->getQueue($queue),
            'Entries' => array_map(
                fn (string $payload) => [
                    'Id' => (string) Str::uuid(),
                    'MessageBody' => $payload,
                ],
                $payloads
            ),
        ]);
    }

    public function setConcurrency(int $concurrency): void
    {
        $this->concurrency = $concurrency;
    }
}
