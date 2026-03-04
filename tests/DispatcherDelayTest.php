<?php

namespace Assetplan\Dispatcher\Tests;

use Assetplan\Dispatcher\Dispatcher;
use Assetplan\Dispatcher\Queue\Job;
use Assetplan\Dispatcher\Tests\Mocks\DummyJob;
use Assetplan\Dispatcher\Tests\Mocks\HttpMock;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Hashing\BcryptHasher;

class DispatcherDelayTest extends TestCase
{
    public function testDispatchSendsDelay(): void
    {
        $http = new HttpMock();
        $queue = $this->createMock(Queue::class);

        $dispatcher = new Dispatcher(new BcryptHasher(), $http, $queue);

        $result = $dispatcher->dispatch('test-job', ['foo' => 'bar'], 'default', 120);

        $this->assertFalse($result->failed());
        $this->assertSame(120, $http->lastPostData['delay']);
    }

    public function testReceiveUsesLaterOnWhenDelayExists(): void
    {
        $http = new HttpMock();
        $queue = $this->createMock(Queue::class);

        $queue->expects($this->once())
            ->method('laterOn')
            ->with('emails', 120, $this->isInstanceOf(DummyJob::class))
            ->willReturn('queue-id-1');

        $queue->expects($this->never())->method('pushOn');

        $dispatcher = new Dispatcher(new BcryptHasher(), $http, $queue);

        $result = $dispatcher->receive(DummyJob::class, ['foo' => 'bar'], 'emails', 120);

        $this->assertSame('queue-id-1', $result);
    }

    public function testReceiveBatchUsesDelayPerJobWhenNotBatching(): void
    {
        $http = new HttpMock();
        $queue = $this->createMock(Queue::class);

        $queue->expects($this->once())
            ->method('laterOn')
            ->with('emails', 300, $this->isInstanceOf(DummyJob::class))
            ->willReturn('queue-id-delayed');

        $queue->expects($this->once())
            ->method('pushOn')
            ->with('emails', $this->isInstanceOf(DummyJob::class))
            ->willReturn('queue-id-immediate');

        $dispatcher = new Dispatcher(new BcryptHasher(), $http, $queue);

        $results = $dispatcher->receiveBatch([
            new Job(DummyJob::class, ['foo' => 'first'], 300),
            new Job(DummyJob::class, ['foo' => 'second']),
        ], 'emails', false);

        $this->assertSame([
            ['id' => 'queue-id-delayed'],
            ['id' => 'queue-id-immediate'],
        ], $results);
    }
}
