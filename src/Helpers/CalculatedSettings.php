<?php

namespace Hexbatch\Things\Helpers;

class CalculatedSettings
{

    public function __construct(
        protected int $descendant_limit,
        protected int $data_limit,
        protected int $backoff_data_policy,
    )
    {
    }

    public function getDescendantLimit(): int
    {
        return $this->descendant_limit;
    }

    public function getDataLimit(): int
    {
        return $this->data_limit;
    }

    public function getBackoffDataPolicy(): int
    {
        return $this->backoff_data_policy;
    }


}
