<?php

namespace Assetplan\Dispatcher\Queue;

use DateInterval;
use DateTimeInterface;

class Job
{
    public string $name;

    public array $payload;

    public ?string $queue;

    public int|string|DateTimeInterface|DateInterval|null $delay;

    public function __construct(
        string $name,
        array $payload = [],
        ?string $queue = null,
        int|string|DateTimeInterface|DateInterval|null $delay = null,
    ) {
        $this->name = $name;
        $this->payload = $payload;
        $this->queue = $queue;
        $this->delay = $delay;
    }

    public static function fromJson(string|array $job): self
    {
        if (is_string($job)) {
            $job = json_decode($job, true);
        }

        return static::fromArray($job);
    }

    public static function fromArray(array $job): self
    {
        return new static(
            $job['name'],
            $job['payload'] ?? [],
            $job['queue'] ?? null,
            $job['delay'] ?? null,
        );
    }
}
