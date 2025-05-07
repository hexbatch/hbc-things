<?php

namespace Hexbatch\Things\OpenApi\Hooks\Callplates;


use Hexbatch\Things\Enums\TypeOfCallback;
use Hexbatch\Things\Enums\TypeOfCallbackSharing;
use Hexbatch\Things\Interfaces\ICallplateOptions;
use Illuminate\Http\Request;
use JsonSerializable;
use OpenApi\Attributes as OA;

/**
 * Create a new callplate
 */
#[OA\Schema(schema: 'CallplateParams',title: "Callplate creation data")]

class CallplateParams implements ICallplateOptions, JsonSerializable
{




    public function __construct(
        #[OA\Property( title:"Callback type",description: 'What type of callback is this?')]
        protected ?TypeOfCallback $callback_type = null,


        #[OA\Property( title:"Sharing policy",description: 'Is this shared?')]
        protected ?TypeOfCallbackSharing $sharing = TypeOfCallbackSharing::NO_SHARING,


        #[OA\Property( title:"Seconds this shared is kept",description: 'Use only if shared')]
        protected ?int $ttl_shared = null,


        #[OA\Property( title:"Data template",description: 'The keys that make up the query|body|form|event|xml data')]
        protected array $data_template = [],


        #[OA\Property( title:"Header template",description: 'The keys that make up the header for the http requests')]
        protected array $header_template = [],

        #[OA\Property( title:"Tags",description: 'Tags decide if a callplate is used with a thing or not')]
        protected array $tags = [],


        #[OA\Property( title:"Address",description: 'the url|callable|evemt')]
        protected ?string $address = null,

        protected ?array $from_array = null

    ) {
        if (is_array($this->from_array)) {
            $this->fillFromArray($this->from_array);
        }
    }

    public function jsonSerialize(): array
    {
        return [
          'callback_type' => $this->callback_type->value,
          'sharing' => $this->callback_type->value,
          'ttl_shared' => $this->ttl_shared,
          'data_template' => $this->data_template,
          'header_template' => $this->header_template,
          'tags' => $this->tags,
          'address' => $this->address,
        ];
    }


    public static function fromRequest(Request $request) : static {

        $node = new static();
        $node->fillFromArray(source: $request->all());
        return $node;
    }

    public function fillFromArray(array $source) {
        if ($type = (string)$source['callback_type']??null) {
            $this->callback_type = TypeOfCallback::tryFromInput($type);
        }

        $this->sharing = TypeOfCallbackSharing::NO_SHARING;
        if ($sharing = (string)$source['sharing']??null) {
            $this->sharing = TypeOfCallbackSharing::tryFromInput($sharing);
        }

        if ($ttl = (int)$source['ttl_shared']??null) {
            $this->ttl_shared = $ttl;
        }

        if ($address = (string)$source['address']??null) {
            $this->address = $address;
        }

        if ( ($body = $source['data_template']??null ) && is_array($body)) {
            $this->data_template = $body;
        }

        if ( ($header = $source['header_template']??null ) && is_array($header)) {
            $this->header_template = $header;
        }

        if ( ($ag = $source['tags']??null ) && is_array($ag)) {
            $this->tags = $ag;
        }
    }

    public static function fromArray(array $source) : static {

        $node = new static();
        $node->fillFromArray(source: $source);
        return $node;
    }

    public function getCallbackType(): ?TypeOfCallback
    {
        return $this->callback_type;
    }

    public function getCallbackSharing():  ?TypeOfCallbackSharing
    {
        return $this->sharing;
    }

    public function getDataTemplate(): array
    {
        return $this->data_template;
    }

    public function getHeaderTemplate(): array
    {
        return $this->header_template;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function getAddress(): string
    {
        return $this->address;
    }


    public function getSharedTtl(): ?int
    {
        return $this->ttl_shared;
    }


}
