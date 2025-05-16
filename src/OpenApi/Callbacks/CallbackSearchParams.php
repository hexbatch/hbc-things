<?php

namespace Hexbatch\Things\OpenApi\Callbacks;

use Carbon\Carbon;
use Hexbatch\Things\Enums\TypeOfCallbackStatus;
use Hexbatch\Things\Enums\TypeOfHookMode;
use Hexbatch\Things\Requests\CallbackSearchRequest;
use JsonSerializable;
use OpenApi\Attributes as OA;
use Ramsey\Uuid\Uuid;

#[OA\Schema(schema: 'CallbackSearchParams',title: "Callback search")]

/**
 * Search callbacks
 */
class CallbackSearchParams  implements  JsonSerializable
{


    public function __construct(
        #[OA\Property( title: "Self", format: 'uuid', nullable: true)]
        protected ?string $uuid = null,

        #[OA\Property( title:"Hook",format: 'uuid', nullable: true)]
        protected ?string $hook_uuid = null,

        #[OA\Property( title:"Thing",format: 'uuid', nullable: true)]
        protected ?string $thing_uuid = null,

        #[OA\Property( title:"Error",format: 'uuid', nullable: true)]
        protected ?string $error_uuid = null,

        #[OA\Property( title:"Alert",format: 'uuid', nullable: true)]
        protected ?string $alert_uuid = null,


        #[OA\Property( title:"Code range min", nullable: true)]
        protected ?int $code_range_min = null,

        #[OA\Property( title:"Code range min", nullable: true)]
        protected ?int $code_range_max = null,

        #[OA\Property( title:"Manual", nullable: true)]
        protected ?bool $is_manual = null,

        #[OA\Property( title:"Blocking", nullable: true)]
        protected ?bool $is_blocking = null,

        #[OA\Property( title:"After", nullable: true)]
        protected ?bool $is_after = null,

        #[OA\Property( title:"Sharing", nullable: true)]
        protected ?bool $is_sharing = null,


        #[OA\Property( title:"Status of callback", nullable: true)]
        protected ?TypeOfCallbackStatus $status = null,

        #[OA\Property( title:"Callback type", nullable: true)]
        protected ?TypeOfHookMode $hook_callback_type = null,

        #[OA\Property( title: 'Owner type filter',description: 'Optional type of owner used to filter. Type must exist when set',nullable: true)]
        protected ?string  $owner_type = null,

        #[OA\Property( title: 'Owner id filter',description: 'Optional owner used to filter.',nullable: true)]
        protected ?string  $owner_id = null,


        #[OA\Property( title: 'Ran at range min', description: "Iso 8601 datetime string", format: 'datetime', example: "2025-01-25T15:00:59-06:00", nullable: true)]
        public ?string $ran_at_min = null,

        #[OA\Property( title: 'Ran at range max', description: "Iso 8601 datetime string", format: 'datetime', example: "2025-01-25T15:00:59-06:00", nullable: true)]
        public ?string $ran_at_max = null,

        #[OA\Property( title: 'Created at range min', description: "Iso 8601 datetime string", format: 'datetime', example: "2025-01-25T15:00:59-06:00", nullable: true)]
        public ?string $created_at_min = null,

        #[OA\Property( title: 'Created at range max', description: "Iso 8601 datetime string", format: 'datetime', example: "2025-01-25T15:00:59-06:00", nullable: true)]
        public ?string $created_at_max = null
    ) {

    }


    public function jsonSerialize(): array
    {
        $arr = [];
        $arr['uuid'] = $this->uuid;
        $arr['hook_uuid'] = $this->hook_uuid;
        $arr['thing_uuid'] = $this->thing_uuid;
        $arr['error_uuid'] = $this->error_uuid;
        $arr['alert_uuid'] = $this->alert_uuid;
        $arr['code_range_min'] = $this->code_range_min;
        $arr['code_range_max'] = $this->code_range_max;
        $arr['is_manual'] = $this->is_manual;
        $arr['is_blocking'] = $this->is_blocking;
        $arr['is_after'] = $this->is_after;
        $arr['is_sharing'] = $this->is_sharing;
        $arr['hook_callback_type'] = $this->hook_callback_type->value;
        $arr['status'] = $this->status->value;
        $arr['ran_at_min'] = $this->ran_at_min;
        $arr['ran_at_max'] = $this->ran_at_max;
        $arr['created_at_min'] = $this->ran_at_max;
        $arr['created_at_max'] = $this->ran_at_max;
        $arr['owner_type'] = $this->owner_type;
        $arr['owner_id'] = $this->owner_id;

        return $arr;
    }




    public function fillFromArray(array $source) {


        if ($mode = (string)($source['status']??null)) {
            $this->status = TypeOfCallbackStatus::tryFromInput($mode);
        }

        if ($hook_callback_type = (string)($source['hook_callback_type']??null) ) {
            $this->hook_callback_type = TypeOfHookMode::tryFromInput($hook_callback_type);
        }


        if (($uuid = (string)($source['hook_uuid']??null)) && Uuid::isValid($uuid)) {
            $this->hook_uuid = $uuid;
        }

        if (($uuid = (string)($source['uuid']??null)) && Uuid::isValid($uuid)) {
            $this->uuid = $uuid;
        }

        if (($uuid = (string)($source['thing_uuid']??null)) && Uuid::isValid($uuid)) {
            $this->thing_uuid = $uuid;
        }

        if (($uuid = (string)($source['error_uuid']??null)) && Uuid::isValid($uuid)) {
            $this->error_uuid = $uuid;
        }

        if (($uuid = (string)($source['alert_uuid']??null)) && Uuid::isValid($uuid)) {
            $this->alert_uuid = $uuid;
        }

        if (array_key_exists('is_manual',$source)) {
            $this->is_manual =  filter_var($source['is_manual']??false, FILTER_VALIDATE_BOOL, FILTER_REQUIRE_SCALAR);
        }

        if (array_key_exists('is_blocking',$source)) {
            $this->is_blocking =  filter_var($source['is_blocking']??false, FILTER_VALIDATE_BOOL, FILTER_REQUIRE_SCALAR);
        }

        if (array_key_exists('is_after',$source)) {
            $this->is_after =  filter_var($source['is_after']??false, FILTER_VALIDATE_BOOL, FILTER_REQUIRE_SCALAR);
        }

        if (array_key_exists('is_sharing',$source)) {
            $this->is_sharing =  filter_var($source['is_sharing']??false, FILTER_VALIDATE_BOOL, FILTER_REQUIRE_SCALAR);
        }

        if (array_key_exists('code_range_min',$source)) {
            if ($code_range_min = (int)$source['code_range_min'] ?? null) {
                $this->code_range_min = $code_range_min;
            }
        }

        if (array_key_exists('code_range_max',$source)) {
            if ($code_range_max = (int)$source['code_range_max'] ?? null) {
                $this->code_range_max = $code_range_max;
            }
        }


        if (array_key_exists('ran_at_min',$source)) {
            if ($time_string = (string)$source['ran_at_min'] ?? null) {
                $this->ran_at_min = Carbon::parse($time_string)->timezone('UTC')->toIso8601String();
            }
        }

        if (array_key_exists('ran_at_max',$source)) {
            if ($time_string = (string)$source['ran_at_max'] ?? null) {
                $this->ran_at_max = Carbon::parse($time_string)->timezone('UTC')->toIso8601String();
            }
        }

        if (array_key_exists('created_at_min',$source)) {
            if ($time_string = (string)$source['created_at_min'] ?? null) {
                $this->created_at_min = Carbon::parse($time_string)->timezone('UTC')->toIso8601String();
            }
        }

        if (array_key_exists('created_at_max',$source)) {
            if ($time_string = (string)$source['created_at_max'] ?? null) {
                $this->created_at_max = Carbon::parse($time_string)->timezone('UTC')->toIso8601String();
            }
        }

        if ($owner_type = (string)($source['owner_type']??null)) {
            $this->owner_type = $owner_type;
        }

        if ($owner_id = (int)($source['owner_id']??null) ) {
            $this->owner_id = $owner_id;
        }
    }


    public static function fromArray(array $source) : static {

        $node = new static();
        $node->fillFromArray(source: $source);
        return $node;
    }

    public static function fromRequest(CallbackSearchRequest $request) : static {

        $node = new static();
        $node->fillFromArray(source: $request->validated());
        return $node;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function getHookUuid(): ?string
    {
        return $this->hook_uuid;
    }

    public function getThingUuid(): ?string
    {
        return $this->thing_uuid;
    }

    public function getErrorUuid(): ?string
    {
        return $this->error_uuid;
    }

    public function getAlertUuid(): ?string
    {
        return $this->alert_uuid;
    }

    public function getCodeRangeMin(): ?int
    {
        return $this->code_range_min;
    }

    public function getCodeRangeMax(): ?int
    {
        return $this->code_range_max;
    }

    public function isManual(): ?bool
    {
        return $this->is_manual;
    }

    public function isBlocking(): ?bool
    {
        return $this->is_blocking;
    }

    public function isAfter(): ?bool
    {
        return $this->is_after;
    }

    public function isSharing(): ?bool
    {
        return $this->is_sharing;
    }

    public function getStatus(): ?TypeOfCallbackStatus
    {
        return $this->status;
    }

    public function getHookCallbackType(): ?TypeOfHookMode
    {
        return $this->hook_callback_type;
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

    public function getOwnerType(): ?string
    {
        return $this->owner_type;
    }

    public function getOwnerId(): ?string
    {
        return $this->owner_id;
    }


}
