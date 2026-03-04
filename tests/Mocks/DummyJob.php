<?php

namespace Assetplan\Dispatcher\Tests\Mocks;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class DummyJob implements ShouldQueue
{
    use Queueable;

    public ?string $foo;

    public function __construct(?string $foo = null)
    {
        $this->foo = $foo;
    }

    public function handle(): void {}
}
