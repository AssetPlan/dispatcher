<?php

namespace Assetplan\Dispatcher\Support;

final class Result
{
    protected int $jobId;
    protected array $response;
    protected bool $ok;

    public function __construct(int $jobId, array $response)
    {
        if ($jobId == 0) {
            $this->ok = false;
        }

        $this->jobId = $jobId;
        $this->response = $response;
    }

    public function ok()
    {
        return $this->ok;
    }

    public function failed()
    {
        return ! $this->ok;
    }

    public function getJobId()
    {
        return $this->jobId;
    }

    public function getResponseArray()
    {
        return $this->response;
    }
}
