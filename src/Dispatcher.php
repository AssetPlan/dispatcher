<?php

namespace Assetplan\Dispatcher;

use Assetplan\Dispatcher\Queue\Job;
use Assetplan\Dispatcher\Support\Result;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

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

    public function dispatch(
        string $job,
        array $payload = [],
        ?string $queue = 'default',
        int|string|DateTimeInterface|DateInterval|null $delay = null,
    ): Result {
        $fields = $this->requestEnvelope($job, $payload, $queue, $delay);
        $fields['signature'] = $this->signRequest($fields);

        $response = $this->http
            ->withHeaders(['Accept' => 'application/json'])
            ->post(config('dispatcher.url').'/dispatch', $fields);

        if ($response->failed()) {
            return new Result(0, $response->json() ?? ['reason' => $response->reason()]);
        }

        $response = $response->json();

        return new Result($response['id'], $response);
    }

    public function batch(array $jobs, ?string $queue = 'default', bool $shouldBatch = true): Result
    {
        $jobs = collect($jobs)
            ->filter(fn ($job) => $job instanceof Job)
            ->map(fn (Job $job) => [
                'name' => $job->name,
                'payload' => $job->payload,
                'queue' => $job->queue,
                'delay' => $this->serializeDelay($job->delay),
            ])
            ->values()
            ->all();

        $batchId = (string) Str::uuid();
        $payload = [
            'batchId' => $batchId,
            'shouldBatch' => $shouldBatch,
            'batchSize' => count($jobs),
            'createdAt' => now()->toIso8601String(),
        ];
        $fields = $this->requestEnvelope($batchId, $payload, $queue, null, $jobs);
        $fields['signature'] = $this->signRequest($fields);

        $response = $this->http
            ->withHeaders(['Accept' => 'application/json'])
            ->post(config('dispatcher.url').'/dispatch/batch', $fields);

        if ($response->failed()) {
            return new Result(0, $response->json() ?? []);
        }

        $response = $response->json();

        return new Result($response['id'] ?? 0, $response);
    }

    public function receive(
        string $job,
        array $payload = [],
        string $queue = 'default',
        int|string|DateTimeInterface|DateInterval|null $delay = null,
    ): mixed {
        $job = $this->makeJob($job, $payload);
        $delay = $this->normalizeDelay($delay);

        if ($delay !== null) {
            return $this->queue->laterOn($queue, $delay, $job);
        }

        return $this->queue->pushOn($queue, $job);
    }

    public function receiveBatch(array $batch, string $queue = 'default', bool $shouldBatch = true): array
    {
        $jobs = collect($batch)->map(function ($item) use ($queue) {
            $job = $item instanceof Job ? $item : Job::fromJson($item);
            $jobQueue = $job->queue ?? $queue;
            $delay = $this->normalizeDelay($job->delay);
            $resolvedJob = $this->makeJob($job->name, $job->payload);

            return compact('jobQueue', 'delay', 'resolvedJob');
        });

        if ($shouldBatch) {
            if ($jobs->contains(fn (array $job) => $job['jobQueue'] !== $queue)) {
                throw new InvalidArgumentException('All jobs in a Laravel batch must use the batch queue.');
            }

            $resolvedJobs = $jobs->map(function (array $job) {
                if ($job['delay'] !== null && method_exists($job['resolvedJob'], 'delay')) {
                    $job['resolvedJob']->delay($job['delay']);
                }

                return $job['resolvedJob'];
            })->all();

            return Bus::batch($resolvedJobs)->onQueue($queue)->dispatch()->jsonSerialize();
        }

        return $jobs->map(function (array $job) {
            if ($job['delay'] !== null) {
                return ['id' => $this->queue->laterOn($job['jobQueue'], $job['delay'], $job['resolvedJob'])];
            }

            return ['id' => $this->queue->pushOn($job['jobQueue'], $job['resolvedJob'])];
        })->all();
    }

    public function sign(string $job, array $payload = []): string
    {
        return $this->hasher->make($job.json_encode($payload).config('dispatcher.secret'));
    }

    public function verify(string $job, array $payload = [], string $signature = ''): bool
    {
        try {
            return $this->hasher->check($job.json_encode($payload).config('dispatcher.secret'), $signature);
        } catch (RuntimeException) {
            return false;
        }
    }

    public function signRequest(array $requestData): string
    {
        return $this->hasher->make(json_encode($this->canonicalEnvelope($requestData)).config('dispatcher.secret'));
    }

    public function verifyRequest(array $requestData, string $signature = ''): bool
    {
        try {
            if ($this->hasher->check(json_encode($this->canonicalEnvelope($requestData)).config('dispatcher.secret'), $signature)) {
                return true;
            }

            return $this->isLegacyEnvelope($requestData)
                && $this->verify($requestData['job'] ?? '', $requestData['payload'] ?? [], $signature);
        } catch (RuntimeException) {
            return false;
        }
    }

    public static function isValidWireDelay(mixed $delay): bool
    {
        if (is_int($delay)) {
            return $delay >= 0;
        }

        if (! is_string($delay) || trim($delay) === '') {
            return false;
        }

        $date = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $delay);
        $errors = DateTimeImmutable::getLastErrors();

        return $date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0));
    }

    protected function requestEnvelope(string $job, array $payload, ?string $queue, mixed $delay, ?array $batch = null): array
    {
        $queue = $queue ?? 'default';
        if ($queue === '') {
            throw new InvalidArgumentException('Queue must be a non-empty string.');
        }

        return [
            'job' => $job,
            'payload' => $payload,
            'queue' => $queue,
            'delay' => $this->serializeDelay($delay),
            'batch' => $batch,
        ];
    }

    protected function canonicalEnvelope(array $requestData): array
    {
        return $this->sortArrayRecursively([
            'job' => $requestData['job'] ?? null,
            'payload' => $requestData['payload'] ?? [],
            'queue' => $requestData['queue'] ?? 'default',
            'delay' => $requestData['delay'] ?? null,
            'batch' => $requestData['batch'] ?? null,
        ]);
    }

    protected function isLegacyEnvelope(array $requestData): bool
    {
        return ($requestData['queue'] ?? 'default') === 'default'
            && ($requestData['delay'] ?? null) === null
            && ($requestData['batch'] ?? null) === null;
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
        if ($delay === null) {
            return null;
        }
        if ($delay instanceof DateInterval) {
            $now = Carbon::now();

            return (int) $now->diffInSeconds($now->copy()->add($delay));
        }
        if ($delay instanceof DateTimeInterface) {
            return Carbon::instance($delay)->toIso8601String();
        }
        if (is_int($delay) || (is_string($delay) && ctype_digit($delay))) {
            $delay = (int) $delay;
            if ($delay >= 0) {
                return $delay;
            }
        }
        if (is_string($delay) && self::isValidWireDelay($delay)) {
            return $delay;
        }

        throw new InvalidArgumentException('Delay must be non-negative integer seconds or an ISO 8601 date.');
    }

    protected function normalizeDelay(int|string|DateTimeInterface|DateInterval|null $delay): int|DateTimeInterface|null
    {
        $delay = $this->serializeDelay($delay);

        return is_string($delay) ? Carbon::parse($delay) : $delay;
    }

    protected function makeJob(string $job, array $payload = []): mixed
    {
        if (config('dispatcher.aliases.'.$job)) {
            $job = config('dispatcher.aliases.'.$job);
        }

        return app()->make($job, $payload);
    }
}
