<?php

namespace Assetplan\Dispatcher\Tests;

use Assetplan\Dispatcher\DispatcherServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            DispatcherServiceProvider::class,
        ];
    }
}
