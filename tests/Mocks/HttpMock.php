<?php

namespace Assetplan\Dispatcher\Tests\Mocks;

use Illuminate\Http\Client\PendingRequest;

class HttpMock extends PendingRequest
{
    public array $lastPostData = [];

    public bool $shouldFail = false;

    public array $response = ['id' => 'mock-id'];

    public function withHeaders(array $headers)
    {
        return $this;
    }

    public function post(string $url, $data = [])
    {
        $this->lastPostData = $data;

        return $this;
    }

    public function json()
    {
        return $this->response;
    }

    public function failed($shouldFail = false)
    {
        if ($shouldFail) {
            return true;
        }

        return $this->shouldFail;
    }
}
