<?php

namespace Hexbatch\Things\OpenApi\Hooks;


use Hexbatch\Things\Enums\TypeOfCallback;
use Hexbatch\Things\Enums\TypeOfHookMode;
use Hexbatch\Things\Interfaces\IThingAction;
use Hexbatch\Things\Interfaces\IThingOwner;
use Hexbatch\Things\Models\ThingHook;
use Hexbatch\Things\Requests\HookRequest;
use JsonSerializable;
use OpenApi\Attributes as OA;
use Ramsey\Uuid\Uuid;

/**
 * Create a new hook
 */
#[OA\Schema(schema: 'HookParams',title: "Hook creation data")]

class HookParams implements JsonSerializable
{



    public function __construct(
        #[OA\Property( title: 'Mode', description: 'Each hook must have a mode set', nullable: false)]
        protected ?TypeOfHookMode $mode = null,


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


        #[OA\Property( title: 'Tags filter', description: 'Optional tags used to filter.', items: new OA\Items(), nullable: true)]
        /**
         * @var string[] $tags
         */
        protected ?array $tags = null,


        #[OA\Property( title: 'Active',description: 'If false, the callback is not used until this is changed.',nullable: true)]
        protected ?bool             $hook_on = null,


        #[OA\Property( title: 'Blocking',description: 'If true, then blocks.',nullable: true)]
        protected ?bool            $is_blocking = null,

        #[OA\Property( title: 'Writing',description: 'If true writes to thing or thing parent.',nullable: true)]
        protected ?bool            $is_writing = null,

        #[OA\Property( title: 'Sharing',description: 'If true the callback is shared among descendants.',nullable: true)]
        protected ?bool            $is_sharing = null,


        #[OA\Property( title: 'Manual',description: 'If true the callback is manually entered.',nullable: true)]
        protected ?bool            $is_manual = null,

        #[OA\Property( title: 'After',description: 'If true the callback is run after the thing is run.',nullable: true)]
        protected ?bool            $is_after = null,


        #[OA\Property( title:"Callback type",description: 'What type of callback is this?')]
        protected ?TypeOfCallback $callback_type = null,




        #[OA\Property( title:"Seconds this shared is kept",description: 'Use only if shared')]
        protected ?int $ttl_shared = null,

        #[OA\Property( title:"Priority",description: 'Order of blocking hooks run')]
        protected ?int $priority = null,


        #[OA\Property( title:"Data template",description: 'The keys that make up the query|body|form|event data',items: new OA\Items())]
        /** @var mixed[] $data_template */
        protected ?array $data_template = null,


        #[OA\Property( title:"Header template",description: 'The keys that make up the header for the http requests',items: new OA\Items())]
        /** @var mixed[] $header_template */
        protected ?array $header_template = null,


        #[OA\Property( title:"Address",description: 'the url|callable|evemt')]
        protected ?string $address = null,


        #[OA\Property( title: 'Owner type filter',description: 'Filter to run on things by this owner type. Type must exist when set',nullable: true)]
        protected ?string  $filter_owner_type = null,

        #[OA\Property( title: 'Owner id filter',description: 'Filter to run on things by one owner.',nullable: true)]
        protected ?string  $filter_owner_id = null,

        /**
         * @var mixed[]|null
         */
        protected ?array $from_array = null

    )
    {
    }


    public function jsonSerialize(): array
    {
        return [
            'mode' => $this->mode->value,
            'name' => $this->name,
            'notes' => $this->notes,
            'owner_type' => $this->owner?->getOwnerType(),
            'owner_id' => $this->owner?->getOwnerUuid() ,
            'action_type' => $this->action_type,
            'action_id' => $this->getActionGuid()?: $this->getActionId(),
            'filter_owner_id' => $this->getFilterOwnerGuid()?: $this->getFilterOwnerId(),
            'filter_owner_type' => $this->filter_owner_type,
            'hook_on' => $this->hook_on,
            'is_writing' => $this->is_writing,
            'is_sharing' => $this->is_sharing,
            'is_blocking' => $this->is_blocking,
            'is_manual' => $this->is_manual,
            'is_after' => $this->is_after,
            'tags' => $this->tags,

            'callback_type' => $this->callback_type->value,
            'ttl_shared' => $this->ttl_shared,
            'priority' => $this->priority,
            'data_template' => $this->data_template,
            'header_template' => $this->header_template,
            'address' => $this->address
        ];

    }

    public static function fromArray(array $source) : static {

        $node = new static();
        $node->fillFromArray(source: $source);
        return $node;
    }

    public function fillFromArray(array $source) {


        if ($mode = (string)($source['mode']??null) ) {
            $this->mode = TypeOfHookMode::tryFromInput($mode);
        }


        if ($name = (string)($source['name']??null) ) {
            $this->name = $name;
        }

        if ($notes = (string)($source['notes']??null))  {
            $this->notes = $notes;
        }

        if ($action_type = (string)($source['action_type']??null)) {
            $this->action_type = $action_type;
        }

        if ($action_id = (string)($source['action_id']??null) ) {
            $this->action_id = $action_id;
        }

        if ($owner_type = (string)($source['filter_owner_type']??null)) {
            $this->filter_owner_type = $owner_type;
        }

        if ($owner_id = (string)($source['filter_owner_id']??null) ) {
            $this->filter_owner_id = $owner_id;
        }

        if (array_key_exists('hook_on',$source)) {
            $this->hook_on =  filter_var($source['hook_on']??false, FILTER_VALIDATE_BOOL, FILTER_REQUIRE_SCALAR);
        }

        if (array_key_exists('is_blocking',$source)) {
            $this->is_blocking =  filter_var($source['is_blocking']??false, FILTER_VALIDATE_BOOL, FILTER_REQUIRE_SCALAR);
        }

        if (array_key_exists('is_writing',$source)) {
            $this->is_writing =  filter_var($source['is_writing']??false, FILTER_VALIDATE_BOOL, FILTER_REQUIRE_SCALAR);
        }

        if (array_key_exists('is_sharing',$source)) {
            $this->is_sharing =  filter_var($source['is_sharing']??false, FILTER_VALIDATE_BOOL, FILTER_REQUIRE_SCALAR);
        }

        if (array_key_exists('is_manual',$source)) {
            $this->is_manual =  filter_var($source['is_manual']??false, FILTER_VALIDATE_BOOL, FILTER_REQUIRE_SCALAR);
        }

        if (array_key_exists('is_after',$source)) {
            $this->is_after =  filter_var($source['is_after']??false, FILTER_VALIDATE_BOOL, FILTER_REQUIRE_SCALAR);
        }


        if ( ($ag = ($source['tags']??null) ) && is_array($ag)) {
            $this->tags = $ag;
        }

        if ($type = (string)($source['callback_type']??null) ) {
            $this->callback_type = TypeOfCallback::tryFromInput($type);
        }

        if (array_key_exists('ttl_shared',$source)) {
            if ($ttl = (int)($source['ttl_shared'] ?? null)) {
                $this->ttl_shared = $ttl;
            }
        }

        if (array_key_exists('priority',$source)) {
            if ($priority = (int)($source['priority'] ?? null)) {
                $this->priority = $priority;
            }
        }

        if ($address = (string)($source['address']??null) ) {
            $this->address = $address;
        }

        if ( ($body = ($source['data_template']??null) ) && is_array($body)) {
            $this->data_template = $body;
        }

        if ( ($header = ($source['header_template']??null) ) && is_array($header)) {
            $this->header_template = $header;
        }
    }

    public static function fromRequest(HookRequest $request) : static {

        $node = new static();
        $node->fillFromArray(source: $request->validated());
        return $node;
    }

    public function getFilterOwnerType(): ?string
    {
        return $this->filter_owner_type;
    }

    public function getFilterOwnerId(): ?int
    {

        if (Uuid::isValid($this->filter_owner_id)) {
            if ($this->action_type) {
                $owner = ThingHook::resolveOwner(owner_type: $this->filter_owner_type,owner_uuid: $this->filter_owner_id);
                if (!$owner) {
                    throw new \LogicException("[getFilterOwnerId] Owner id $this->filter_owner_id for type $this->filter_owner_type was passed in without validation");
                }
                return $owner->getOwnerId();
            }
            return null;
        }
        return $this->filter_owner_id;
    }

    public function getFilterOwnerGuid(): ?int
    {

        if (!Uuid::isValid($this->filter_owner_id)) {
            if ($this->action_type) {
                $owner = ThingHook::resolveOwner(owner_type: $this->filter_owner_type,owner_id: $this->filter_owner_id);
                if (!$owner) {
                    throw new \LogicException("[getFilterOwnerId] Owner id $this->filter_owner_id for type $this->filter_owner_type was passed in without validation");
                }
                return $owner->getOwnerUuid();
            }
            return null;
        }
        return $this->filter_owner_id;
    }


    public function getHookOwner(): ?IThingOwner
    {
       return $this->owner;
    }

    public function getActionType(): ?string
    {

        return $this->action_type;
    }

    public function getActionId(): ?int
    {
        if (Uuid::isValid($this->action_id)) {
            if ($this->action_type) {
                $action = ThingHook::resolveAction(action_type: $this->action_type,uuid: $this->action_id);
                if (!$action) {
                    throw new \LogicException("[getActionId] Action id $this->action_id for type $this->action_type was passed in without validation");
                }
                return $action->getActionId();
            }
            return null;
        }
        return $this->action_id;
    }


    public function getActionGuid(): ?string
    {
        if (!Uuid::isValid($this->action_id)) {
            if ($this->action_type) {
                $action = ThingHook::resolveAction(action_type: $this->action_type,action_id: $this->action_id);
                if (!$action) {
                    throw new \LogicException("[getActionGuid] Action uuid $this->action_id for type $this->action_type was passed in without validation");
                }
                return $action->getActionUuid();
            }
            return null;
        }
        return $this->action_id;
    }



    public function getHookTags(): ?array
    {
        return $this->tags;
    }


    public function isHookOn(): ?bool
    {
        return $this->hook_on;
    }

    public function getHookMode(): ?TypeOfHookMode
    {
        return $this->mode;
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


    public function getDataTemplate(): ?array
    {
        return $this->data_template;
    }

    public function getHeaderTemplate(): ?array
    {
        return $this->header_template;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }


    public function getSharedTtl(): ?int
    {
        return $this->ttl_shared;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }


    public function setHookOwner(?IThingOwner $owner): self
    {
        $this->owner = $owner;
        return $this;
    }

    public function isBlocking(): ?bool
    {
        return $this->is_blocking;
    }

    public function isWriting(): ?bool
    {
        return $this->is_writing;
    }

    public function isSharing(): ?bool
    {
        return $this->is_sharing;
    }

    public function isManual(): ?bool
    {
        return $this->is_manual;
    }

    public function isAfter(): ?bool
    {
        return $this->is_after;
    }
}
