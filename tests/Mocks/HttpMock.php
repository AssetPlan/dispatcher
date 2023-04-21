<?php

namespace Assetplan\Dispatcher\Tests\Mocks;

use Illuminate\Http\Client\PendingRequest;

class HttpMock extends PendingRequest
{

    public function withHeaders(array $headers)
    {
        return $this;
    }

    public function post(string $url, $data=[])
    {
        // return new Response([]);
        return $this;
    }

    public function json()
    {
        return [];
    }

    public function failed($shouldFail=false)
    {
        return !$shouldFail;
    }


}
