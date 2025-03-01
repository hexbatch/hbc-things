<?php

namespace Hexbatch\Things\Helpers;

use Hexbatch\Things\Interfaces\ICallResponse;

class CallResponse implements ICallResponse
{

    public function __construct(
        protected int $code,
        protected bool $successful,
        protected ?array $data
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
        return $this->data;
    }
}
