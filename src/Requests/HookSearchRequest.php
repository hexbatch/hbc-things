<?php


namespace Hexbatch\Things\Requests;


use Hexbatch\Things\Enums\TypeOfCallbackStatus;
use Hexbatch\Things\Enums\TypeOfCallback;
use Hexbatch\Things\Enums\TypeOfHookMode;
use Hexbatch\Things\Helpers\ThingUtilities;
use Hexbatch\Things\Rules\ValidateAction;
use Hexbatch\Things\Rules\ValidateOwner;
use Hexbatch\Things\Rules\ValidateOwnerFilter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


/**
 * @property null|bool is_blocking
 * @property null|bool is_sharing
 * @property null|bool is_after
 * @property null|bool is_manual
 * @property null|bool is_writing
 * @property null|bool hook_on
 * @property null|string|array tags
 */
class HookSearchRequest extends FormRequest
{


    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {



        if($this->is_sharing && $this->is_sharing !== '') { $this->merge(['is_sharing' => ThingUtilities::boolishToBool(val: $this->is_sharing)]);}
        if($this->is_manual && $this->is_manual !== '') { $this->merge(['is_manual' => ThingUtilities::boolishToBool(val: $this->is_manual)]);}
        if($this->is_writing && $this->is_writing !== '') { $this->merge(['is_writing' => ThingUtilities::boolishToBool(val: $this->is_writing)]);}
        if($this->is_blocking && $this->is_blocking !== '') { $this->merge(['is_blocking' => ThingUtilities::boolishToBool(val: $this->is_blocking)]);}
        if($this->hook_on && $this->hook_on !== '') { $this->merge(['hook_on' => ThingUtilities::boolishToBool(val: $this->hook_on)]);}
        if($this->is_after && $this->is_after !== '') { $this->merge(['is_after' => ThingUtilities::boolishToBool(val: $this->is_after)]);}

        $this->merge([
            'tags' => ThingUtilities::getArray(source: $this->tags),
        ]);
    }
    /**
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        return [

            'action_id' => ['integer', 'nullable', new ValidateAction],
            'action_type' => ['string', 'nullable', new ValidateAction],
            'filter_owner_id' => ['integer', 'nullable', new ValidateOwnerFilter],
            'filter_owner_type' => ['string', 'nullable', new ValidateOwnerFilter],

            'owner_id' => ['integer', 'nullable', new ValidateOwner],
            'owner_type' => ['string', 'nullable', new ValidateOwner],

            'uuid' => ['uuid', 'nullable'],

            /** @uses ThingHook::hook_callback_type */
            'callback_type' => ['nullable',Rule::enum(TypeOfCallback::class)],

            /** @uses ThingHook::hook_mode */
            'mode' => ['nullable',Rule::enum(TypeOfHookMode::class)],

            /** @uses ThingHook::hook_tags */
            'tags' => ['nullable',Rule::array()],
            "tags.*" => "required|string|distinct|min:1",


            'ttl_shared_min' => ['nullable', Rule::numeric()->min(0)->integer()],
            'ttl_shared_max' => ['nullable', Rule::numeric()->min(0)->integer()],

            'priority_min' => ['nullable', Rule::numeric()->min(0)->integer()],
            'priority_max' => ['nullable', Rule::numeric()->min(0)->integer()],

            'status' => ['nullable',Rule::enum(TypeOfCallbackStatus::class)],
            'hook_callback_type' => ['nullable',Rule::enum(TypeOfCallback::class)],

            'ran_at_min' => ['date', 'nullable'],
            'ran_at_max' => ['date', 'nullable'],
            'created_at_min' => ['date', 'nullable'],
            'created_at_max' => ['date', 'nullable'],


            /** @uses ThingHook::is_on */
            "hook_on" => ['nullable','boolean'],

            /** @uses ThingHook::is_blocking */
            "is_blocking" => ['nullable','boolean'],

            /** @uses ThingHook::is_writing_data_to_thing */
            "is_writing" => ['nullable','boolean'],

            /** @uses ThingHook::is_sharing */
            "is_sharing" => ['nullable','boolean'],

            /** @uses ThingHook::is_after */
            "is_after" =>['nullable','boolean'],

            /** @uses ThingHook::is_manual */
            "is_manual" => ['nullable','boolean'],



        ];
    }
}
