<?php

namespace Hexbatch\Things\OpenApi\Hooks\Callplates;


use Hexbatch\Things\Enums\TypeOfCallback;
use Hexbatch\Things\Interfaces\ICallplateOptions;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Create a new callplate
 */
#[OA\Schema(schema: 'CallplateParams',title: "Callplate creation data")]

class CallplateParams implements ICallplateOptions
{


    #[OA\Property( title:"Callback type",description: 'What type of callback is this?')]
    public TypeOfCallback $callback_type;


    #[OA\Property( title:"Data template",description: 'The keys that make up the query|body|form|event|xml data')]
    public array $data_template = [];


    #[OA\Property( title:"Header template",description: 'The keys that make up the header for the http requests')]
    public array $header_template = [];

    #[OA\Property( title:"Tags",description: 'Tags decide if a callplate is used with a thing or not')]
    public array $tags = [];


    #[OA\Property( title:"Url",description: 'http requests need a url')]
    public ?string $url = null;

    #[OA\Property( title:"Event name",description: 'events need a name')]
    public ?string $event_name = null;

    #[OA\Property( title:"Class name",description: 'function calls need a fully qualified class name')]
    public ?string $class_path = null;



    const array ARRAY_KEYS = [
        'data_template',
        'header_template',
        'tags',
    ];

    const array STRING_KEYS = [
        'url',
        'event_name',
        'class_path',
    ];

    public static function fromRequest(Request $request) : static {
        $node = new static();


        foreach (static::STRING_KEYS as $key) {
            if ($request->has($key)) {
                $node->$key = $request->request->getString($key)?: null;
            }
        }

        foreach (static::ARRAY_KEYS as $key) {
            if ($request->has($key)) {
                $node->$key = (array)$request->get($key,[]);
            }
        }

        $node->callback_type = TypeOfCallback::tryFromInput($request->get('callback_type'));
        return $node;
    }

    public function getCallbackType(): TypeOfCallback
    {
        return $this->callback_type;
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

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getEventFilter(): ?string
    {
        return $this->event_name;
    }

    public function getClass(): ?string
    {
        return $this->class_path;
    }


}
