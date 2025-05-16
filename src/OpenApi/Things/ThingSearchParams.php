<?php

namespace Hexbatch\Things\OpenApi\Things;

use Carbon\Carbon;
use Hexbatch\Things\Enums\TypeOfThingStatus;
use Hexbatch\Things\Requests\ThingSearchRequest;
use JsonSerializable;
use OpenApi\Attributes as OA;
use Ramsey\Uuid\Uuid;

#[OA\Schema(schema: 'ThingSearchParams',title: "Thing search")]

/**
 * Search things
 */
class ThingSearchParams  implements  JsonSerializable
{


    public function __construct(
        #[OA\Property( title: "Self", format: 'uuid', nullable: true)]
        protected ?string $uuid = null,


        #[OA\Property( title:"Error",format: 'uuid', nullable: true)]
        protected ?string $error_uuid = null,


        #[OA\Property( title:"Async", nullable: true)]
        protected ?bool $async = null,

        #[OA\Property( title:"Root", nullable: true)]
        protected ?bool $is_root = null,

        #[OA\Property( title: 'Action type filter',description: 'Optional type of action used to filter. Type must exist when set',nullable: true)]
        protected ?string  $action_type = null,

        #[OA\Property( title: 'Action id filter',description: 'Optional action used to filter.',nullable: true)]
        protected ?string  $action_id = null,


        #[OA\Property( title: 'Owner type filter',description: 'Optional type of owner used to filter. Type must exist when set',nullable: true)]
        protected ?string  $owner_type = null,

        #[OA\Property( title: 'Owner id filter',description: 'Optional owner used to filter.',nullable: true)]
        protected ?string  $owner_id = null,


        #[OA\Property( title:"Tags",items: new OA\Items(),nullable: true)]
        /** @var string[] $tags */
        protected ?array $tags = null,

        #[OA\Property( title:"Status of hook", nullable: true)]
        protected ?TypeOfThingStatus $status = null,


        #[OA\Property( title: 'Ran at range min', description: "Iso 8601 datetime string", format: 'datetime', example: "2025-01-25T15:00:59-06:00", nullable: true)]
        public ?string $ran_at_min = null,

        #[OA\Property( title: 'Ran at range max', description: "Iso 8601 datetime string", format: 'datetime', example: "2025-01-25T15:00:59-06:00", nullable: true)]
        public ?string $ran_at_max = null,

        #[OA\Property( title: 'Created at range min', description: "Iso 8601 datetime string", format: 'datetime', example: "2025-01-25T15:00:59-06:00", nullable: true)]
        public ?string $created_at_min = null,

        #[OA\Property( title: 'Created at range max', description: "Iso 8601 datetime string", format: 'datetime', example: "2025-01-25T15:00:59-06:00", nullable: true)]
        public ?string $created_at_max = null,

        #[OA\Property( title: 'Started at range min', description: "Iso 8601 datetime string", format: 'datetime', example: "2025-01-25T15:00:59-06:00", nullable: true)]
        public ?string $started_at_min = null,

        #[OA\Property( title: 'Started at range max', description: "Iso 8601 datetime string", format: 'datetime', example: "2025-01-25T15:00:59-06:00", nullable: true)]
        public ?string $started_at_max = null
    ) {

    }


    public function jsonSerialize(): array
    {
        $arr = [];
        $arr['uuid'] = $this->uuid;
        $arr['error_uuid'] = $this->error_uuid;

        $arr['action_type'] = $this->action_type;
        $arr['action_id'] = $this->action_id;

        $arr['owner_type'] = $this->owner_type;
        $arr['owner_id'] = $this->owner_id;

        $arr['async'] = $this->async;
        $arr['is_root'] = $this->is_root;
        $arr['status'] = $this->status->value;



        $arr['ran_at_min'] = $this->ran_at_min;
        $arr['ran_at_max'] = $this->ran_at_max;
        $arr['created_at_min'] = $this->ran_at_max;
        $arr['created_at_max'] = $this->ran_at_max;

        $arr['started_at_min'] = $this->started_at_min;
        $arr['started_at_max'] = $this->started_at_max;

        return $arr;
    }




    public function fillFromArray(array $source) {

        if ($action_type = (string)($source['action_type']??null)) {
            $this->action_type = $action_type;
        }

        if ($action_id = (int)($source['action_id']??null) ) {
            $this->action_id = $action_id;
        }

        if ($owner_type = (string)($source['owner_type']??null)) {
            $this->owner_type = $owner_type;
        }

        if ($owner_id = (int)($source['owner_id']??null) ) {
            $this->owner_id = $owner_id;
        }

        if ($mode = (string)($source['status']??null)) {
            $this->status = TypeOfThingStatus::tryFromInput($mode);
        }



        if (($uuid = (string)($source['uuid']??null)) && Uuid::isValid($uuid)) {
            $this->uuid = $uuid;
        }


        if (($uuid = (string)($source['error_uuid']??null)) && Uuid::isValid($uuid)) {
            $this->error_uuid = $uuid;
        }



        if (array_key_exists('async',$source) && !is_null($source['async'])) {
            $this->async =  filter_var($source['async'], FILTER_VALIDATE_BOOL, FILTER_REQUIRE_SCALAR);
        }

        if (array_key_exists('is_root',$source) && !is_null($source['is_root'])) {
            $this->is_root =  filter_var($source['is_root'], FILTER_VALIDATE_BOOL, FILTER_REQUIRE_SCALAR);
        }



        if (array_key_exists('ran_at_max',$source)) {
            if ($time_string = (string)$source['ran_at_max'] ?? null) {
                $this->ran_at_max = Carbon::parse($time_string)->timezone('UTC')->toIso8601String();
            }
        }

        if (array_key_exists('ran_at_min',$source)) {
            if ($time_string = (string)$source['ran_at_min'] ?? null) {
                $this->ran_at_min = Carbon::parse($time_string)->timezone('UTC')->toIso8601String();
            }
        }


        if (array_key_exists('created_at_max',$source)) {
            if ($time_string = (string)$source['created_at_max'] ?? null) {
                $this->created_at_max = Carbon::parse($time_string)->timezone('UTC')->toIso8601String();
            }
        }

        if (array_key_exists('created_at_min',$source)) {
            if ($time_string = (string)$source['created_at_min'] ?? null) {
                $this->created_at_min = Carbon::parse($time_string)->timezone('UTC')->toIso8601String();
            }
        }


        if (array_key_exists('started_at_max',$source)) {
            if ($time_string = (string)$source['started_at_max'] ?? null) {
                $this->started_at_max = Carbon::parse($time_string)->timezone('UTC')->toIso8601String();
            }
        }

        if (array_key_exists('started_at_min',$source)) {
            if ($time_string = (string)$source['started_at_min'] ?? null) {
                $this->started_at_min = Carbon::parse($time_string)->timezone('UTC')->toIso8601String();
            }
        }
    }


    public static function fromArray(array $source) : static {

        $node = new static();
        $node->fillFromArray(source: $source);
        return $node;
    }

    public static function fromRequest(ThingSearchRequest $request) : static {

        $node = new static();
        $node->fillFromArray(source: $request->validated());
        return $node;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function getErrorUuid(): ?string
    {
        return $this->error_uuid;
    }

    public function getAsync(): ?bool
    {
        return $this->async;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function getStatus(): ?TypeOfThingStatus
    {
        return $this->status;
    }

    public function getRanAtMin(): ?string
    {
        return $this->ran_at_min;
    }

    public function getRanAtMax(): ?string
    {
        return $this->ran_at_max;
    }

    public function getCreatedAtMin(): ?string
    {
        return $this->created_at_min;
    }

    public function getCreatedAtMax(): ?string
    {
        return $this->created_at_max;
    }

    public function getStartedAtMin(): ?string
    {
        return $this->started_at_min;
    }

    public function getStartedAtMax(): ?string
    {
        return $this->started_at_max;
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

    public function getIsRoot(): ?bool
    {
        return $this->is_root;
    }

    public function setIsRoot(?bool $is_root): void
    {
        $this->is_root = $is_root;
    }



}
