<?php

namespace Hexbatch\Things\OpenApi\Hooks\Callplates;


use Hexbatch\Things\Enums\TypeOfCallback;
use Hexbatch\Things\Enums\TypeOfHookBlocking;
use Hexbatch\Things\Enums\TypeOfHookMode;
use Hexbatch\Things\Enums\TypeOfHookPosition;
use Hexbatch\Things\Enums\TypeOfHookScope;
use Hexbatch\Things\Interfaces\ICallplateOptions;
use Hexbatch\Things\Interfaces\IHookParams;
use Hexbatch\Things\Interfaces\IThingAction;
use Hexbatch\Things\Interfaces\IThingOwner;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Create a new callplate
 */
#[OA\Schema(schema: 'HookParams',title: "Hook creation data")]

class HookParams implements IHookParams
{
    public static function fromRequest(Request $request) : static
    {
        $node = new static();
        return $node;
    }

    public function getHookOwner(): ?IThingOwner
    {
        // TODO: Implement getHookOwner() method.
    }

    public function getHookAction(): ?IThingAction
    {
        // TODO: Implement getHookAction() method.
    }

    public function getConstantData(): array
    {
        // TODO: Implement getConstantData() method.
    }

    public function getHookTags(): array
    {
        // TODO: Implement getHookTags() method.
    }

    public function getHookCallbackTimeToLive(): ?int
    {
        // TODO: Implement getHookCallbackTimeToLive() method.
    }

    public function isHookOn(): bool
    {
        // TODO: Implement isHookOn() method.
    }

    public function getHookMode(): TypeOfHookMode
    {
        // TODO: Implement getHookMode() method.
    }

    public function getHookBlocking(): TypeOfHookBlocking
    {
        // TODO: Implement getHookBlocking() method.
    }

    public function getHookScope(): TypeOfHookScope
    {
        // TODO: Implement getHookScope() method.
    }

    public function getHookPosition(): TypeOfHookPosition
    {
        // TODO: Implement getHookPosition() method.
    }

    public function getHookName(): string
    {
        // TODO: Implement getHookName() method.
    }

    public function getHookNotes(): ?string
    {
        // TODO: Implement getHookNotes() method.
    }

    public function getCallplates(): array
    {
        // TODO: Implement getCallplates() method.
    }
}
