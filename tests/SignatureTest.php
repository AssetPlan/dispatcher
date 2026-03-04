<?php

use Assetplan\Dispatcher\Tests\TestCase;

class SignatureTest extends TestCase
{
    public function testSignature()
    {
        $payload = [
            'foo' => 'bar',
            'baz' => 'qux',
        ];

        $dispatcher = app()->make('dispatcher');

        $signature = $dispatcher->sign('test', $payload);

        $verified = $dispatcher->verify('test', $payload, $signature);

        $this->assertTrue($verified);
    }

    public function testSignatureWithDifferentPayload()
    {
        $payload = [
            'foo' => 'bar',
            'baz' => 'qux',
        ];

        $dispatcher = app()->make('dispatcher');

        $signature = $dispatcher->sign('test', $payload);

        $payload['foo'] = 'not-bar';

        $verified = $dispatcher->verify('test', $payload, $signature);

        $this->assertFalse($verified);
    }

    public function testSignatureWithDifferentSignature()
    {
        $payload = [
            'foo' => 'bar',
            'baz' => 'qux',
        ];

        $signature = 'not-a-valid-signature';
        $dispatcher = app()->make('dispatcher');

        $verified = $dispatcher->verify('test', $payload, $signature);

        $this->assertFalse($verified);
    }

    public function testRequestSignature()
    {
        $dispatcher = app()->make('dispatcher');

        $request = [
            'job' => 'App\\Jobs\\SyncSomething',
            'payload' => ['foo' => 'bar'],
            'queue' => 'emails',
            'delay' => 120,
        ];

        $signature = $dispatcher->signRequest($request);

        $verified = $dispatcher->verifyRequest($request, $signature);

        $this->assertTrue($verified);
    }

    public function testRequestSignatureFailsWhenQueueChanges()
    {
        $dispatcher = app()->make('dispatcher');

        $request = [
            'job' => 'App\\Jobs\\SyncSomething',
            'payload' => ['foo' => 'bar'],
            'queue' => 'emails',
            'delay' => 120,
        ];

        $signature = $dispatcher->signRequest($request);

        $request['queue'] = 'critical';

        $verified = $dispatcher->verifyRequest($request, $signature);

        $this->assertFalse($verified);
    }

    public function testRequestVerificationSupportsLegacySignature()
    {
        $dispatcher = app()->make('dispatcher');

        $payload = [
            'foo' => 'bar',
            'baz' => 'qux',
        ];

        $legacySignature = $dispatcher->sign('test', $payload);

        $verified = $dispatcher->verifyRequest([
            'job' => 'test',
            'payload' => $payload,
            'queue' => 'emails',
            'delay' => 180,
        ], $legacySignature);

        $this->assertTrue($verified);
    }
}
