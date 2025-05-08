<?php

namespace Hexbatch\Things\OpenApi\Hooks;


use Hexbatch\Things\Enums\TypeOfCallback;
use Hexbatch\Things\Enums\TypeOfCallbackSharing;
use Hexbatch\Things\Enums\TypeOfHookMode;
use Hexbatch\Things\Enums\TypeOfHookScope;
use Hexbatch\Things\Interfaces\IHookParams;
use Hexbatch\Things\Interfaces\IThingAction;
use Hexbatch\Things\Interfaces\IThingOwner;
use Illuminate\Http\Request;
use JsonSerializable;
use OpenApi\Attributes as OA;

/**
 * Create a new hook
 */
#[OA\Schema(schema: 'HookParams',title: "Hook creation data")]

class HookParams implements IHookParams, JsonSerializable
{


    protected ?IThingAction $action = null;

    public function __construct(
        #[OA\Property( title: 'Mode',description: 'Each hook must have a mode set',nullable: false)]
        protected ?TypeOfHookMode $mode = null,


        #[OA\Property( title: 'Scope',description: 'Scope of the hook action',nullable: false)]
        protected TypeOfHookScope $scope = TypeOfHookScope::CURRENT,


        #[OA\Property( title: 'Name',description: 'Hooks can have names',nullable: true)]
        protected ?string  $name = null,

        #[OA\Property( title: 'Notes',description: 'Any notes (text)',nullable: true)]
        protected ?string  $notes = null,

        //set by code
        protected ?IThingOwner $owner = null,

        #[OA\Property( title: 'Action type filter',description: 'Optional type of action used to filter. Type must exist when set',nullable: true)]
        protected ?string  $action_type = null,

        #[OA\Property( title: 'Action id filter',description: 'Optional action used to filter.',nullable: true)]
        protected ?string  $action_id = null,

        #[OA\Property( title: 'Callback constants to send',description: 'Optional data to send in each callback.',nullable: true)]
        protected array $constant_data = [],

        #[OA\Property( title: 'Tags filter',description: 'Optional tags used to filter.',nullable: true)]
        protected array $tags = [],


        #[OA\Property( title: 'Active',description: 'If false, the callback is not used until this is changed.',nullable: true)]
        protected bool $hook_on = true,


        #[OA\Property( title: 'Blocking',description: 'If true, then blocks.',nullable: true)]
        protected bool $is_blocking = true,

        #[OA\Property( title: 'Writing',description: 'If true writes to thing or thing parent.',nullable: true)]
        protected bool $is_writing = true,


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


        #[OA\Property( title:"Address",description: 'the url|callable|evemt')]
        protected ?string $address = null,

        protected ?array $from_array = null

    )
    {
    }


    public function jsonSerialize(): array
    {
        return [
            'mode' => $this->mode->value,
            'scope' => $this->scope->value,
            'name' => $this->name,
            'notes' => $this->notes,
            'owner_type' => $this->owner?->getOwnerType(),
            'owner_id' => $this->owner?->getOwnerId(),
            'action_type' => $this->action?->getActionType(),
            'action_id' => $this->action?->getActionId(),
            'hook_on' => $this->hook_on,
            'is_writing' => $this->is_writing,
            'is_blocking' => $this->is_blocking,
            'constant_data' => $this->constant_data,
            'tags' => $this->tags,

            'callback_type' => $this->callback_type->value,
            'sharing' => $this->callback_type->value,
            'ttl_shared' => $this->ttl_shared,
            'data_template' => $this->data_template,
            'header_template' => $this->header_template,
            'address' => $this->address,
        ];
    }

    public static function fromArray(array $source) : static {

        $node = new static();
        $node->fillFromArray(source: $source);
        return $node;
    }

    public function fillFromArray(array $source) {


        if ($mode = (string)$source['mode']??null) {
            $this->mode = TypeOfHookMode::tryFromInput($mode);
        }


        if ($scope = (string)$source['scope']??null) {
            $this->scope = TypeOfHookScope::tryFromInput($scope);
        }

        if ($name = (string)$source['name']??null) {
            $this->name = $name;
        }

        if ($notes = (string)$source['notes']??null) {
            $this->notes = $notes;
        }

        if ($action_type = (string)$source['action_type']??null) {
            $this->action_type = $action_type;
        }

        if ($action_id = (int)$source['action_id']??null) {
            $this->action_id = $action_id;
        }

        $this->hook_on =  filter_var($source['hook_on']??false, FILTER_VALIDATE_BOOL, FILTER_REQUIRE_SCALAR);
        $this->is_blocking =  filter_var($source['is_blocking']??false, FILTER_VALIDATE_BOOL, FILTER_REQUIRE_SCALAR);
        $this->is_writing =  filter_var($source['is_writing']??false, FILTER_VALIDATE_BOOL, FILTER_REQUIRE_SCALAR);

        if ( ($const = $source['constant_data']??null ) && is_array($const)) {
            $this->constant_data = $const;
        }


        if ( ($ag = $source['tags']??null ) && is_array($ag)) {
            $this->tags = $ag;
        }

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
    }

    public static function fromRequest(Request $request) : static {

        $node = new static();
        $node->fillFromArray(source: $request->all());
        return $node;
    }


    public function getHookOwner(): ?IThingOwner
    {
       return $this->owner;
    }

    public function getHookAction(): ?IThingAction
    {
        return $this->action;
    }

    public function getConstantData(): array
    {
        return $this->constant_data;
    }

    public function getHookTags(): array
    {
        return $this->tags;
    }


    public function isHookOn(): bool
    {
        return $this->hook_on;
    }

    public function getHookMode(): ?TypeOfHookMode
    {
        return $this->mode;
    }

    public function getHookScope(): TypeOfHookScope
    {
        return $this->scope;
    }


    public function getHookName(): ?string
    {
        return $this->name;
    }

    public function getHookNotes(): ?string
    {
        return $this->notes;
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

    public function getAddress(): string
    {
        return $this->address;
    }


    public function getSharedTtl(): ?int
    {
        return $this->ttl_shared;
    }


    public function setHookOwner(?IThingOwner $owner): IHookParams
    {
        $this->owner = $owner;
        return $this;
    }

    public function isBlocking(): bool
    {
        return $this->is_blocking;
    }

    public function isWriting(): bool
    {
        return $this->is_writing;
    }
}
