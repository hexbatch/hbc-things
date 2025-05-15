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
 * @property null|string|array data_template
 * @property null|string|array header_template
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
            'data_template' => ThingUtilities::getArray(source: $this->data_template),
            'header_template' => ThingUtilities::getArray(source: $this->header_template)
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


            /** @uses ThingHook::action_type_id */
            'action_id' => ['integer', 'nullable', new ValidateAction],

            /** @uses ThingHook::action_type */
            'action_type' => ['string', 'nullable', new ValidateAction],

            /** @uses ThingHook::hook_callback_type */
            'callback_type' => ['nullable',Rule::enum(TypeOfCallback::class)],

            /** @uses ThingHook::hook_mode */
            'mode' => ['nullable',Rule::enum(TypeOfHookMode::class)],

            /** @uses ThingHook::hook_tags */
            'tags' => ['nullable',Rule::array()],
            "tags.*" => "required|string|distinct|min:1",


            /** @uses ThingHook::hook_data_template */
            'data_template' => ['nullable',Rule::array()],


            /** @uses ThingHook::hook_header_template */
            'header_template' => ['nullable',Rule::array()],

            /** @uses ThingHook::ttl_shared */
            'ttl_shared' => ['nullable','numeric','integer','min:0'],

            /** @uses ThingHook::hook_priority */
            'priority' => ['nullable','numeric','integer','min:0'],

            /** @uses ThingHook::hook_notes */
            "notes" => ['nullable','string'],

            /** @uses ThingHook::hook_name */
            "name" => ['nullable','string'],

            /** @uses ThingHook::address */
            "address" => ['nullable','string'],

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
