<?php

namespace Assetplan\Dispatcher\Tests;

use Assetplan\Dispatcher\Dispatcher;
use Assetplan\Dispatcher\Queue\Job;
use Assetplan\Dispatcher\Tests\Mocks\HttpMock;
use DateInterval;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Hashing\BcryptHasher;
use InvalidArgumentException;

class DispatcherContractTest extends TestCase
{
    public function test_dispatch_signs_queue_and_relative_delay(): void
    {
        $http = new HttpMock;
        $dispatcher = new Dispatcher(new BcryptHasher, $http, $this->createMock(Queue::class));

        $dispatcher->dispatch('job', ['id' => 1], 'emails', new DateInterval('PT2M'));

        $this->assertSame('emails', $http->lastPostData['queue']);
        $this->assertSame(120, $http->lastPostData['delay']);
        $this->assertTrue($dispatcher->verifyRequest($http->lastPostData, $http->lastPostData['signature']));
    }

    public function test_request_signature_rejects_changed_envelope_field(): void
    {
        $dispatcher = app()->make('dispatcher');
        $request = ['job' => 'job', 'payload' => ['id' => 1], 'queue' => 'emails', 'delay' => 10, 'batch' => null];
        $signature = $dispatcher->signRequest($request);
        $request['queue'] = 'critical';

        $this->assertFalse($dispatcher->verifyRequest($request, $signature));
    }

    public function test_legacy_signature_cannot_authorize_new_controls(): void
    {
        $dispatcher = app()->make('dispatcher');
        $signature = $dispatcher->sign('job', ['id' => 1]);

        $this->assertTrue($dispatcher->verifyRequest(['job' => 'job', 'payload' => ['id' => 1]], $signature));
        $this->assertFalse($dispatcher->verifyRequest([
            'job' => 'job', 'payload' => ['id' => 1], 'queue' => 'emails', 'delay' => null, 'batch' => null,
        ], $signature));
    }

    public function test_non_batched_jobs_use_their_own_queue_and_delay(): void
    {
        $queue = $this->createMock(Queue::class);
        $queue->expects($this->once())->method('laterOn')->with('emails', 30, $this->anything())->willReturn('delayed');
        $queue->expects($this->once())->method('pushOn')->with('reports', $this->anything())->willReturn('immediate');
        $dispatcher = new Dispatcher(new BcryptHasher, new HttpMock, $queue);

        $result = $dispatcher->receiveBatch([
            new Job(DummyJob::class, [], 'emails', 30),
            new Job(DummyJob::class, [], 'reports'),
        ], 'default', false);

        $this->assertSame([['id' => 'delayed'], ['id' => 'immediate']], $result);
    }

    public function test_invalid_delay_is_rejected_before_request(): void
    {
        $dispatcher = new Dispatcher(new BcryptHasher, new HttpMock, $this->createMock(Queue::class));

        $this->expectException(InvalidArgumentException::class);
        $dispatcher->dispatch('job', [], 'default', -1);
    }
}
