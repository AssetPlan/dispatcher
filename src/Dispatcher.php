<?php

namespace Assetplan\Dispatcher;

use Assetplan\Dispatcher\Support\Result;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Http\Client\PendingRequest;

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
            ->post(config('dispatcher.url') . '/dispatch', [
                'job' => $job,
                'payload' => $payload,
                'signature' => $signature,
                'queue' => $queue ?? 'default',
            ]);

        if($response->failed()) {
            return new Result(0, $response->json());
        }

        $response = $response->json();

        return new Result($response['jobId'], $response);
    }

    public function receive(string $job, array $payload = [], string $queue = 'default'): mixed
    {
        $job = app()->make($job, $payload);
        return $this->queue->pushOn($queue, $job);
    }

    public function verify(string $job, array $payload = [], string $signature = '')
    {
        return $this->hasher->check($job . json_encode($payload) . config('dispatcher.secret'), $signature);
    }

    public function sign(string $job, array $payload = [])
    {
        return $this->hasher->make($job . json_encode($payload) . config('dispatcher.secret'));
    }
}
