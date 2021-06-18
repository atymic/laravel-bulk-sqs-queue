<?php
declare(strict_types=1);

namespace Atymic\AsyncSqsQueue\Exception;

use Exception;
use Illuminate\Support\Collection;

class BulkSqsDispatchFailed extends Exception
{
    public static function withFailureResponses(Collection $failures): self
    {
        $message = $failures
            ->map(fn(array $fail) => sprintf('%s failed with %s, message `%s`', $fail['Id'], $fail['Code'], $fail['Message']))
            ->prepend('Bulk dispatch failed, errors:')
            ->join("\n");

        return new self($message);
    }
}
