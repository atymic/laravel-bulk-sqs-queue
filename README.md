# Laravel SQS Bulk Queue

[![Latest Version on Packagist](https://img.shields.io/packagist/v/atymic/laravel-bulk-sqs-queue.svg?style=flat-square)](https://packagist.org/packages/atymic/laravel-bulk-sqs-queue)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/atymic/laravel-bulk-sqs-queue/run-tests?label=tests)](https://github.com/atymic/laravel-bulk-sqs-queue/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/atymic/laravel-bulk-sqs-queue/Check%20&%20fix%20styling?label=code%20style)](https://github.com/atymic/laravel-bulk-sqs-queue/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/atymic/laravel-bulk-sqs-queue.svg?style=flat-square)](https://packagist.org/packages/atymic/laravel-bulk-sqs-queue)

## Installation

You can install the package via composer:

```bash
composer require atymic/laravel-bulk-sqs-queue
```

## How it works

By default, Laravel allows you to easily execute a batch of jobs with `Queue::bulk()` method or with built-in [job batching](https://laravel.com/docs/master/queues#job-batching). Both methods accept an array of jobs for dispatch and loop over every job, making one HTTP request for each job.

This isn't an issue when you are dispatching a few jobs, but there's two major issues with bigger batches:

- Waiting for 1000 http requests (even SQS, with 20-50ms latency) is two to five seconds, that's slow!
- SQS is billed per request, and they support batching of up to 10 messages for the same cost as a single `sendMessage` call. That's a 10x cost saving!

But AWS SQS has a [batch action](https://docs.aws.amazon.com/AWSSimpleQueueService/latest/SQSDeveloperGuide/sqs-batch-api-actions.html) that let you group up to 10 messages with a single request, in order to reduce costs. Under the hood, this package override the bulk method to:

- Batch jobs into 10 per request, or 200kb chunks (SQS has a maximum of 256kb, including request overhead/etc)
- Dispatch those batches asynchronously, up to `$concurrency` at a time (default 5)

That's about it. With this package, the laravel queue system should work the exact same as normal. You should have the exact same result in your application and you AWS SQS dashboard but with a smaller AWS bill :)

## Usage

This package provides a queue connector called `sqs-bulk`. Inside your `queue.php` config file, add it to `connections`:

```php
'connections' => [
        'sqs-bulk' => [
            'driver'       => 'sqs-bulk',
            'key'          => env('AWS_ACCESS_KEY_ID'),
            'secret'       => env('AWS_SECRET_ACCESS_KEY'),
            'prefix'       => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue'        => env('SQS_QUEUE', 'default'),
            'suffix'       => env('SQS_SUFFIX'),
            'region'       => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
            'concurrency'  => 5, // Set the request concurrency, defaults to 5
        ],
]
```

Then you can start a queue worker for the new "connection" and the given queue ('default' queue can be override with `SQS_QUEUE`):

```bash
php artisan queue:work sqs-bulk --queue=default
```

It will process new jobs as they are pushed onto the queue. You can group jobs with queue's bulk method:

```php
Illuminate\Support\Facades\Queue::bulk([
    new \App\Jobs\Foo,
    new \App\Jobs\Bar,
    new \App\Jobs\Baz,
], '', 'default');
```

or with laravel's built-in [job batching](https://laravel.com/docs/master/queues#job-batching) feature:

```php
Illuminate\Support\Facades\Bus::batch([                                                                                                                                           new \App\Jobs\JobToto,                                                                                                                                 new \App\Jobs\JobToto,                                                                                                                             ])->dispatch();
    new \App\Jobs\Foo,
    new \App\Jobs\Bar,
    new \App\Jobs\Baz,
])->name('My sqs batch')->onQueue('default')->dispatch();
```

It should have processed the 3 jobs by creating 3 "messages" on AWS SQS but with only 1 request.

### Failing jobs

This package only affect the way jobs are transmitted to AWS SQS. Once received by AWS SQS, they are handled as separate messages. From AWS SQS perspective, there is no relation between messages. The batch request can result in a combination of successful and unsuccessful actions that doesn't affect each others.

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
