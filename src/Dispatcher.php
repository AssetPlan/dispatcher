<?php

namespace Assetplan\Dispatcher;

use Assetplan\Dispatcher\Queue\Job;
use Assetplan\Dispatcher\Support\Result;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

class Dispatcher
{
    protected Hasher $hasher;

    protected PendingRequest $http;

    protected Queue $queue;

    public function __construct(Hasher $hasher, PendingRequest $http, Queue $queue)
    {
        $this->hasher = $hasher;
        $this->http = $http;
        $this->queue = $queue;
    }

    public function dispatch(string $job, array $payload = [], $queue = 'default'): Result
    {
        $signature = $this->sign($job, $payload);
        $response = $this->http
            ->withHeaders(['Accept' => 'application/json'])
            ->post(config('dispatcher.url').'/dispatch', [
                'job' => $job,
                'payload' => $payload,
                'signature' => $signature,
                'queue' => $queue ?? 'default',
            ]);

        if ($response->failed()) {
            return new Result(0, $response->json());
        }

        $response = $response->json();

        return new Result($response['id'], $response);
    }

    public function batch(array $jobs, string $queue = 'default', bool $shouldBatch = true): Result
    {
        $jobs = collect($jobs)->filter(fn ($job) => $job instanceof Job);

        $batchId = Str::uuid();

        $payload = [
            'batchId' => $batchId,
            'shouldBatch' => $shouldBatch,
            'batchSize' => count($jobs),
            'createdAt' => now(),
        ];

        $signature = $this->sign($batchId, $payload);
        $fields = [
            'job' => $batchId,
            'payload' => $payload,
            'batch' => $jobs,
            'signature' => $signature,
            'queue' => $queue ?? 'default',
        ];

        $response = $this->http
            ->withHeaders(['Accept' => 'application/json'])
            ->post(config('dispatcher.url').'/dispatch/batch', $fields);

        if ($response->failed()) {
            return new Result(0, $response->json());
        }

        $response = $response->json();

        return new Result($response['id'], $response);
    }

    public function receive(string $job, array $payload = [], string $queue = 'default'): mixed
    {
        $job = $this->makeJob($job, $payload);

        return $this->queue->pushOn($queue, $job);
    }

    public function receiveBatch(array $batch, string $queue = 'default', bool $shouldBatch = true): array
    {
        $jobs = [];

        foreach ($batch as $job) {
            $job = Job::fromJson($job);
            $jobs[] = $this->makeJob($job->name, $job->payload);
        }
        if ($shouldBatch) {
            return Bus::batch($jobs)->onQueue($queue)->dispatch()->jsonSerialize();
        }

        $results = [];

        foreach ($jobs as $job) {
            $results[] = ['id' => $this->queue->pushOn($queue, $job)];
        }

        return $results;
    }

    protected function makeJob(string $job, array $payload = [])
    {
        if (config('dispatcher.aliases.'.$job)) {
            $job = config('dispatcher.aliases.'.$job);
        }

        return app()->make($job, $payload);
    }

    public function verify(string $job, array $payload = [], string $signature = '')
    {
        return $this->hasher->check($job.json_encode($payload).config('dispatcher.secret'), $signature);
    }

    public function sign(string $job, array $payload = [])
    {
        return $this->hasher->make($job.json_encode($payload).config('dispatcher.secret'));
    }
}
