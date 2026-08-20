<?php

namespace Assetplan\Dispatcher\Tests\Mocks;

use Illuminate\Http\Client\PendingRequest;

class HttpMock extends PendingRequest
{
    public array $lastPostData = [];

    public array $response = ['id' => 'mock-id'];

    public bool $shouldFail = false;

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
        return $shouldFail || $this->shouldFail;
    }
}
