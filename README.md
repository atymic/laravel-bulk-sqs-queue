# Laravel SQS Bulk Queue

[![Latest Version on Packagist](https://img.shields.io/packagist/v/atymic/laravel-bulk-sqs-queue.svg?style=flat-square)](https://packagist.org/packages/atymic/laravel-bulk-sqs-queue)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/atymic/laravel-bulk-sqs-queue/run-tests?label=tests)](https://github.com/atymic/laravel-bulk-sqs-queue/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/atymic/laravel-bulk-sqs-queue/Check%20&%20fix%20styling?label=code%20style)](https://github.com/atymic/laravel-bulk-sqs-queue/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/atymic/laravel-bulk-sqs-queue.svg?style=flat-square)](https://packagist.org/packages/atymic/laravel-bulk-sqs-queue)

Docs TBC :)

## Installation

You can install the package via composer:

```bash
composer require atymic/laravel-bulk-sqs-queue
```

## How it works

By default, the Laravel SQS queue provides a `Queue::bulk()` method, which accepts an array of jobs for dispatch. This loops over every job, making one HTTP
request for each job.

This isn't an issue when you are dispatching a few jobs, but there's two major issues with bigger batches:

- Waiting for 1000 http requests (even SQS, with 20-50ms latency) is two to five seconds, that's slow!
- SQS is billed per request, and they support batching of up to 10 messages for the same cost as a single `sendMessage` call. That's a 10x cost saving!

Under the hood, this package override the bulk method to:

- Batch jobs into 10 per request, or 200kb chunks (SQS has a maximum of 256kb, including request overhead/etc)
- Dispatch those batches asynchronously, up to `$concurrency` at a time (default 5)

That about it. The rest of the queue functions the exact same as normal :)

## Usage

This package provides a queue connector called `sqs-bulk`. Inside your `queue.php` config file, add it to `connections`:

```php
'connections' => [
        'sqs-bulk' => [
            'driver'      => 'sqs-bulk',
            'key'         => env('AWS_KEY', null),
            'secret'      => env('AWS_SECRET', null),
            'prefix'      => env('AWS_PREFIX', null),
            'queue'       => env('AWS_QUEUE', null),
            'region'      => env('AWS_REGION', null),
            'concurrency'  => 5, // Set the request concurrency, defaults to 5
        ],
// [...]
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [atymic](https://github.com/atymic)
- [Laravel-BatchSQS](https://github.com/CoInvestor/Laravel-BatchSQS)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
