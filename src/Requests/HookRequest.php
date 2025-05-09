<?php


namespace Hexbatch\Things\Requests;


use Hexbatch\Things\Models\ThingHook;
use Hexbatch\Things\Enums\TypeOfCallback;
use Hexbatch\Things\Enums\TypeOfHookMode;
use Hexbatch\Things\Helpers\ThingUtilities;
use Hexbatch\Things\Rules\ValidateAction;
use Hexbatch\Things\Rules\ValidateOwner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property null|string|array hook_tags
 * @property null|string|array hook_data_template
 * @property null|string|array hook_header_template
 */
class HookRequest extends FormRequest
{


    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {

        $this->merge([
            'hook_tags' => ThingUtilities::getArray(source: $this->hook_tags),
            'hook_data_template' => ThingUtilities::getArray(source: $this->hook_data_template),
            'hook_header_template' => ThingUtilities::getArray(source: $this->hook_header_template)
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        return [
            /** @uses ThingHook::owner_type_id */
            'owner_type_id' => ['integer', 'nullable', new ValidateOwner],

            /** @uses ThingHook::owner_type */
            'owner_type' => ['string', 'nullable', new ValidateOwner],

            /** @uses ThingHook::action_type_id */
            'action_type_id' => ['integer', 'nullable', new ValidateAction],

            /** @uses ThingHook::action_type */
            'action_type' => ['string', 'nullable', new ValidateAction],

            /** @uses ThingHook::hook_callback_type */
            'hook_callback_type' => [Rule::enum(TypeOfCallback::class)],

            /** @uses ThingHook::hook_mode */
            'hook_mode' => [Rule::enum(TypeOfHookMode::class)],

            /** @uses ThingHook::hook_tags */
            'hook_tags' => ['nullable',Rule::array()],
            "hook_tags.*" => "required|string|distinct|min:1",


            /** @uses ThingHook::hook_data_template */
            'hook_data_template' => ['nullable',Rule::array()],


            /** @uses ThingHook::hook_header_template */
            'hook_header_template' => ['nullable',Rule::array()],

            /** @uses ThingHook::ttl_shared */
            'ttl_shared' => ['nullable','numeric','integer','min:0'],

            /** @uses ThingHook::hook_priority */
            'hook_priority' => ['nullable','numeric','integer','min:0'],

            /** @uses ThingHook::hook_notes */
            "hook_notes" => "nullable|string",

            /** @uses ThingHook::hook_name */
            "hook_name" => "nullable|string",

            /** @uses ThingHook::address */
            "address" => "nullable|string",

            /** @uses ThingHook::is_on */
            "is_on" => "nullable,boolean",

            /** @uses ThingHook::is_blocking */
            "is_blocking" => "nullable,boolean",

            /** @uses ThingHook::is_writing_data_to_thing */
            "is_writing_data_to_thing" => "nullable,boolean",

            /** @uses ThingHook::is_sharing */
            "is_sharing" => "nullable,boolean",

            /** @uses ThingHook::is_after */
            "is_after" => "nullable,boolean",

            /** @uses ThingHook::is_manual */
            "is_manual" => "nullable,boolean",

        ];
    }
}
