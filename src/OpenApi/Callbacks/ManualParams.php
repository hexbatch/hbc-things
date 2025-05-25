<?php

namespace Hexbatch\Things\OpenApi\Callbacks;


use Hexbatch\Things\Interfaces\ICallResponse;
use Hexbatch\Things\Requests\ManualFillRequest;

use JsonSerializable;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response as CodeOf;

#[OA\Schema(schema: 'ManualParams',title: "Manual callback entry")]

/**
 * Fill in a manual callback
 */
class ManualParams  implements  JsonSerializable  , ICallResponse
{


    public function __construct(



        #[OA\Property( title:"Code", nullable: true)]
        protected int $code = CodeOf::HTTP_OK,

        #[OA\Property( title:"Wait timeout", nullable: true)]
        protected ?int $wait_in_seconds = null,

        #[OA\Property( title: "Data", items: new OA\Items(), nullable: true)]

        protected ?array $data = null,


    ) {

    }


    public function jsonSerialize(): array
    {
        $arr = [];
        $arr['code'] = $this->code;
        $arr['wait'] = $this->wait_in_seconds;
        $arr['data'] = $this->data;
        return $arr;
    }




    public function fillFromArray(array $source) {



        if (array_key_exists('code',$source)) {
            $this->code =  (int)$source['code'];
            if ($this->code < 0) { $this->code = 0;}
        }

        if (array_key_exists('wait',$source)) {
            $this->wait_in_seconds =  (int)$source['wait'];
            if ($this->wait_in_seconds < 0) { $this->wait_in_seconds = null;}
        }

        if ( ($data = ($source['data']??null) ) && is_array($data)) {
            $this->data = $data;
        }
    }


    public static function fromArray(array $source) : static {

        $node = new static();
        $node->fillFromArray(source: $source);
        return $node;
    }

    public static function fromRequest(ManualFillRequest $request) : static {

        $node = new static();
        $node->fillFromArray(source: $request->validated());
        return $node;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getData(): ?array
    {
        return $this->data;
    }


    public function getWaitTimeoutInSeconds(): ?int
    {
        return $this->wait_in_seconds;
    }
}
