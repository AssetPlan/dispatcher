<?php

namespace Assetplan\Dispatcher\Queue;

class Job
{
    public string $name;

    public array $payload;

    public function __construct(string $name, array $payload = [])
    {
        $this->name = $name;
        $this->payload = $payload;
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
        return new static($job['name'], $job['payload']);
    }
}
