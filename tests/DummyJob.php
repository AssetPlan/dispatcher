<?php

namespace Assetplan\Dispatcher\Tests;

use Illuminate\Contracts\Queue\ShouldQueue;

class DummyJob implements ShouldQueue
{
    public function handle(): void {}
}
