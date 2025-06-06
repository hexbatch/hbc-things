<?php

namespace Hexbatch\Things\Helpers;

use Hexbatch\Things\Interfaces\ICallResponse;

class CallResponse implements ICallResponse
{

    public function __construct(
        protected int $code,
        protected bool $successful,
        protected null|object|array $data,
        protected ?int $wait_timeout_seconds = null
    )
    {
    }


    public function getCode(): int
    {
       return $this->code;
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    public function getData(): ?array
    {
        if (!$this->data) { return null;}
        if (is_array($this->data) ) {return $this->data;}
        return (array)$this->data;
    }

    public function getWaitTimeoutInSeconds(): ?int
    {
        return $this->wait_timeout_seconds;
    }
}
