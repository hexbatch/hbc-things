<?php

namespace Hexbatch\Things\OpenApi\Hooks;

use Hexbatch\Things\Enums\TypeOfCallback;
use Hexbatch\Things\Enums\TypeOfHookMode;
use Hexbatch\Things\Requests\HookSearchRequest;
use JsonSerializable;
use OpenApi\Attributes as OA;
use Ramsey\Uuid\Uuid;

#[OA\Schema(schema: 'HookSearchParams',title: "Thing search")]

/**
 * Search things
 */
class HookSearchParams  implements  JsonSerializable
{


    public function __construct(
        #[OA\Property( title: "Self", format: 'uuid', nullable: true)]
        protected ?string $uuid = null,


        #[OA\Property( title: 'Action type filter',description: 'Optional type of action used to filter. Type must exist when set',nullable: true)]
        protected ?string  $action_type = null,

        #[OA\Property( title: 'Action id filter',description: 'Optional action used to filter.',nullable: true)]
        protected ?string  $action_id = null,


        #[OA\Property( title: 'Owner type filter',description: 'Optional type of owner used to filter. Type must exist when set',nullable: true)]
        protected ?string  $owner_type = null,

        #[OA\Property( title: 'Owner id filter',description: 'Optional owner used to filter.',nullable: true)]
        protected ?string  $owner_id = null,

        #[OA\Property( title: 'Owner type filter',description: 'Filter to run on things by this owner type. Type must exist when set',nullable: true)]
        protected ?string  $filter_owner_type = null,

        #[OA\Property( title: 'Owner id filter',description: 'Filter to run on things by one owner.',nullable: true)]
        protected ?string  $filter_owner_id = null,

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


        #[OA\Property( title:"Tags",items: new OA\Items(),nullable: true)]
        /** @var string[] $tags */
        protected ?array $tags = null,

        #[OA\Property( title:"Callback type",description: 'Callback type')]
        protected ?TypeOfCallback $callback_type = null,

        #[OA\Property( title:"Hook mode",description: 'Callback type')]
        protected ?TypeOfHookMode $mode = null,


        #[OA\Property( title:"Minimum Seconds this shared is kept",description: 'Used only if shared')]
        protected ?int $ttl_shared_min = null,

        #[OA\Property( title:"Max Seconds this shared is kept",description: 'Used only if shared')]
        protected ?int $ttl_shared_max = null,

        #[OA\Property( title:"Minimum Priority",description: 'Order of blocking hooks run')]
        protected ?int $priority_min = null,

        #[OA\Property( title:"Max Priority",description: 'Order of blocking hooks run')]
        protected ?int $priority_max = null,


    ) {

    }


    public function jsonSerialize(): array
    {
        $arr = [];
        $arr['uuid'] = $this->uuid;

        $arr['filter_owner_type'] = $this->filter_owner_type;
        $arr['filter_owner_id'] = $this->filter_owner_id;

        $arr['action_type'] = $this->action_type;
        $arr['action_id'] = $this->action_id;

        $arr['owner_type'] = $this->owner_type;
        $arr['owner_id'] = $this->owner_id;

        $arr['callback_type'] =  $this->callback_type?->value;
        $arr['mode'] =  $this->mode?->value;

        $arr['hook_on'] =  $this->hook_on;
        $arr['is_writing'] =  $this->is_writing;
        $arr['is_sharing'] =  $this->is_sharing;
        $arr['is_blocking'] =  $this->is_blocking;
        $arr['is_manual'] =  $this->is_manual;
        $arr['is_after'] =  $this->is_after;
        $arr['tags'] =  $this->tags;


        $arr['ttl_shared_min'] =  $this->ttl_shared_min;
        $arr['ttl_shared_max'] =  $this->ttl_shared_max;
        $arr['priority_min'] =  $this->priority_min;
        $arr['priority_max'] =  $this->priority_max;

        return $arr;
    }




    public function fillFromArray(array $source) {

        if (($uuid = (string)($source['uuid']??null)) && Uuid::isValid($uuid)) {
            $this->uuid = $uuid;
        }

        if ($filter_owner_type = (string)($source['filter_owner_type']??null)) {
            $this->filter_owner_type = $filter_owner_type;
        }

        if ($filter_owner_id = (int)($source['filter_owner_id']??null) ) {
            $this->filter_owner_id = $filter_owner_id;
        }




        if ($owner_type = (string)($source['owner_type']??null)) {
            $this->owner_type = $owner_type;
        }

        if ($owner_id = (int)($source['owner_id']??null) ) {
            $this->owner_id = $owner_id;
        }


        if ($mode = (string)($source['mode']??null)) {
            $this->mode = TypeOfHookMode::tryFromInput($mode);
        }

        if ($mode = (string)($source['callback_type']??null)) {
            $this->callback_type = TypeOfCallback::tryFromInput($mode);
        }



        if (array_key_exists('is_blocking',$source)) {
            $this->is_blocking =  filter_var($source['is_blocking']??false, FILTER_VALIDATE_BOOL, FILTER_REQUIRE_SCALAR);
        }

        if (array_key_exists('hook_on',$source)) {
            $this->hook_on =  filter_var($source['hook_on']??false, FILTER_VALIDATE_BOOL, FILTER_REQUIRE_SCALAR);
        }

        if (array_key_exists('is_writing',$source)) {
            $this->is_writing =  filter_var($source['is_writing']??false, FILTER_VALIDATE_BOOL, FILTER_REQUIRE_SCALAR);
        }

        if (array_key_exists('is_manual',$source)) {
            $this->is_manual =  filter_var($source['is_manual']??false, FILTER_VALIDATE_BOOL, FILTER_REQUIRE_SCALAR);
        }

        if (array_key_exists('is_sharing',$source)) {
            $this->is_sharing =  filter_var($source['is_sharing']??false, FILTER_VALIDATE_BOOL, FILTER_REQUIRE_SCALAR);
        }

        if (array_key_exists('is_after',$source)) {
            $this->is_after =  filter_var($source['is_after']??false, FILTER_VALIDATE_BOOL, FILTER_REQUIRE_SCALAR);
        }


        if ( ($ag = ($source['tags']??null) ) && is_array($ag)) {
            $this->tags = $ag;
        }

        if (array_key_exists('ttl_shared_min',$source)) {
            if ($ttl = (int)($source['ttl_shared_min'] ?? null)) {
                $this->ttl_shared_min = $ttl;
            }
        }

        if (array_key_exists('ttl_shared_max',$source)) {
            if ($ttl = (int)($source['ttl_shared_max'] ?? null)) {
                $this->ttl_shared_max = $ttl;
            }
        }

        if (array_key_exists('priority_min',$source)) {
            if ($priority = (int)($source['priority_min'] ?? null)) {
                $this->priority_min = $priority;
            }
        }

        if (array_key_exists('priority_max',$source)) {
            if ($priority = (int)($source['priority_max'] ?? null)) {
                $this->priority_max = $priority;
            }
        }

        if ($action_type = (string)($source['action_type']??null)) {
            $this->action_type = $action_type;
        }

        if ($action_id = (int)($source['action_id']??null) ) {
            $this->action_id = $action_id;
        }
    }


    public static function fromArray(array $source) : static {

        $node = new static();
        $node->fillFromArray(source: $source);
        return $node;
    }

    public static function fromRequest(HookSearchRequest $request) : static {

        $node = new static();
        $node->fillFromArray(source: $request->validated());
        return $node;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function getActionType(): ?string
    {
        return $this->action_type;
    }

    public function getActionId(): ?string
    {
        return $this->action_id;
    }

    public function getOwnerType(): ?string
    {
        return $this->owner_type;
    }

    public function getOwnerId(): ?string
    {
        return $this->owner_id;
    }

    public function getFilterOwnerType(): ?string
    {
        return $this->filter_owner_type;
    }

    public function getFilterOwnerId(): ?string
    {
        return $this->filter_owner_id;
    }

    public function getHookOn(): ?bool
    {
        return $this->hook_on;
    }

    public function getIsBlocking(): ?bool
    {
        return $this->is_blocking;
    }

    public function getIsWriting(): ?bool
    {
        return $this->is_writing;
    }

    public function getIsSharing(): ?bool
    {
        return $this->is_sharing;
    }

    public function getIsManual(): ?bool
    {
        return $this->is_manual;
    }

    public function getIsAfter(): ?bool
    {
        return $this->is_after;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function getCallbackType(): ?TypeOfCallback
    {
        return $this->callback_type;
    }

    public function getMode(): ?TypeOfHookMode
    {
        return $this->mode;
    }

    public function getTtlSharedMin(): ?int
    {
        return $this->ttl_shared_min;
    }

    public function getTtlSharedMax(): ?int
    {
        return $this->ttl_shared_max;
    }

    public function getPriorityMin(): ?int
    {
        return $this->priority_min;
    }

    public function getPriorityMax(): ?int
    {
        return $this->priority_max;
    }







}
