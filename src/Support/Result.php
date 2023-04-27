<?php

namespace Assetplan\Dispatcher\Support;

final class Result
{
    protected string $id;

    protected array $response;

    protected bool $ok = true;

    public function __construct($id, array $response)
    {
        if ($id == 0) {
            $this->ok = false;
        }

        $this->id = $id;
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

    public function getId()
    {
        return $this->id;
    }

    public function getResponseArray()
    {
        return $this->response;
    }
}
