<?php

namespace Assetplan\Dispatcher;

use Assetplan\Dispatcher\Queue\Job;
use Assetplan\Dispatcher\Support\Result;
use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use InvalidArgumentException;

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

    public function dispatch(string $job, array $payload = [], $queue = 'default', int|string|DateTimeInterface|DateInterval|null $delay = null): Result
    {
        $serializedDelay = $this->serializeDelay($delay);

        $fields = [
            'job' => $job,
            'payload' => $payload,
            'queue' => $queue ?? 'default',
            'delay' => $serializedDelay,
        ];

        $signature = $this->signRequest($fields);
        $response = $this->http
            ->withHeaders(['Accept' => 'application/json'])
            ->post(config('dispatcher.url').'/dispatch', array_merge($fields, [
                'signature' => $signature,
            ]));

        if ($response->failed()) {
            $response = ($response->json() ?? ['reason' => $response->reason()]);

            return new Result(0, $response);
        }

        $response = $response->json();

        return new Result($response['id'], $response);
    }

    public function batch(array $jobs, string $queue = 'default', bool $shouldBatch = true): Result
    {
        $jobs = collect($jobs)
            ->filter(fn ($job) => $job instanceof Job)
            ->map(fn (Job $job) => [
                'name' => $job->name,
                'payload' => $job->payload,
                'delay' => $this->serializeDelay($job->delay),
            ])
            ->values();

        $batchId = Str::uuid();

        $payload = [
            'batchId' => $batchId,
            'shouldBatch' => $shouldBatch,
            'batchSize' => count($jobs),
            'createdAt' => now(),
        ];

        $fields = [
            'job' => $batchId,
            'payload' => $payload,
            'batch' => $jobs->all(),
            'queue' => $queue ?? 'default',
        ];

        $signature = $this->signRequest($fields);
        $fields['signature'] = $signature;

        $response = $this->http
            ->withHeaders(['Accept' => 'application/json'])
            ->post(config('dispatcher.url').'/dispatch/batch', $fields);

        if ($response->failed()) {
            return new Result(0, $response->json());
        }

        $response = $response->json();

        return new Result($response['id'], $response);
    }

    public function receive(string $job, array $payload = [], string $queue = 'default', int|string|DateTimeInterface|DateInterval|null $delay = null): mixed
    {
        $job = $this->makeJob($job, $payload);

        $normalizedDelay = $this->normalizeDelay($delay);

        if (! is_null($normalizedDelay)) {
            return $this->queue->laterOn($queue, $normalizedDelay, $job);
        }

        return $this->queue->pushOn($queue, $job);
    }

    public function receiveBatch(array $batch, string $queue = 'default', bool $shouldBatch = true): array
    {
        $jobs = [];

        foreach ($batch as $job) {
            $job = Job::fromJson($job);

            $resolvedJob = $this->makeJob($job->name, $job->payload);
            $delay = $this->normalizeDelay($job->delay);

            if (! is_null($delay) && method_exists($resolvedJob, 'delay')) {
                $resolvedJob->delay($delay);
            }

            $jobs[] = [
                'job' => $resolvedJob,
                'delay' => $delay,
            ];
        }

        if ($shouldBatch) {
            return Bus::batch(array_map(fn (array $job) => $job['job'], $jobs))->onQueue($queue)->dispatch()->jsonSerialize();
        }

        $results = [];

        foreach ($jobs as $job) {
            if (! is_null($job['delay'])) {
                $results[] = ['id' => $this->queue->laterOn($queue, $job['delay'], $job['job'])];
                continue;
            }

            $results[] = ['id' => $this->queue->pushOn($queue, $job['job'])];
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
        try {
            return $this->hasher->check($job.json_encode($payload).config('dispatcher.secret'), $signature);
        } catch (\RuntimeException $e) {
            // Laravel 11+ throws an exception if the signature is not a valid bcrypt hash
            return false;
        }
    }

    public function sign(string $job, array $payload = [])
    {
        return $this->hasher->make($job.json_encode($payload).config('dispatcher.secret'));
    }

    public function signRequest(array $requestData = []): string
    {
        $normalized = $this->normalizeRequestData($requestData);

        return $this->hasher->make(json_encode($normalized).config('dispatcher.secret'));
    }

    public function verifyRequest(array $requestData = [], string $signature = ''): bool
    {
        try {
            $normalized = $this->normalizeRequestData($requestData);

            if ($this->hasher->check(json_encode($normalized).config('dispatcher.secret'), $signature)) {
                return true;
            }

            return $this->verify(
                $requestData['job'] ?? '',
                $requestData['payload'] ?? [],
                $signature,
            );
        } catch (\RuntimeException $e) {
            return false;
        }
    }

    protected function normalizeRequestData(array $requestData): array
    {
        $data = [
            'job' => $requestData['job'] ?? null,
            'payload' => $requestData['payload'] ?? [],
            'queue' => $requestData['queue'] ?? 'default',
            'delay' => $requestData['delay'] ?? null,
            'batch' => $requestData['batch'] ?? null,
        ];

        return $this->sortArrayRecursively($data);
    }

    protected function sortArrayRecursively(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sortArrayRecursively($value);
            }
        }

        ksort($data);

        return $data;
    }

    protected function serializeDelay(int|string|DateTimeInterface|DateInterval|null $delay): int|string|null
    {
        if (is_null($delay)) {
            return null;
        }

        if ($delay instanceof DateInterval) {
            return now()->add($delay)->toIso8601String();
        }

        if ($delay instanceof DateTimeInterface) {
            return Carbon::instance($delay)->toIso8601String();
        }

        if (is_string($delay) && is_numeric($delay)) {
            return (int) $delay;
        }

        return $delay;
    }

    protected function normalizeDelay(int|string|DateTimeInterface|DateInterval|null $delay): int|DateTimeInterface|null
    {
        if (is_null($delay)) {
            return null;
        }

        if ($delay instanceof DateInterval) {
            return now()->add($delay);
        }

        if ($delay instanceof DateTimeInterface) {
            return $delay;
        }

        if (is_string($delay) && trim($delay) === '') {
            return null;
        }

        if (is_string($delay) && is_numeric($delay)) {
            return (int) $delay;
        }

        if (is_string($delay)) {
            return Carbon::parse($delay);
        }

        if (is_int($delay)) {
            return $delay;
        }

        throw new InvalidArgumentException('Invalid delay value.');
    }
}
