<?php

namespace Assetplan\Dispatcher\Tests;

use Assetplan\Dispatcher\Dispatcher;
use Assetplan\Dispatcher\Tests\Mocks\HttpMock;
use Assetplan\Dispatcher\Tests\TestCase;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Hashing\BcryptHasher;
use Assetplan\Dispatcher\Support\Result;

class ResultTest extends TestCase
{

    public function testDispatchShouldReturnResult()
    {
        $http = new HttpMock();

        // Config::shouldReceive('get')->with('dispatcher.url')->andReturn('https://example.com');
        $dispatcher = new Dispatcher(new BcryptHasher, $http, app(Queue::class));

        // Call the dispatch method and check the result
        $result = $dispatcher->dispatch('test', ['foo' => 'bar']);
        $this->assertInstanceOf(Result::class, $result);
    }
}
