<?php

use Assetplan\Dispatcher\Tests\TestCase;

class SignatureTest extends TestCase
{
    public function test_signature()
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

    public function test_signature_with_different_payload()
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

    public function test_signature_with_different_signature()
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
}
