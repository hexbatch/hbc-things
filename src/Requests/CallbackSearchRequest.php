<?php


namespace Hexbatch\Things\Requests;


use Hexbatch\Things\Enums\TypeOfCallbackStatus;
use Hexbatch\Things\Enums\TypeOfCallback;
use Hexbatch\Things\Helpers\ThingUtilities;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


/**
 * @property null|bool is_blocking
 * @property null|bool is_sharing
 * @property null|bool is_after
 * @property null|bool is_manual
 * @property null|bool is_manual_notice
 */
class CallbackSearchRequest extends FormRequest
{


    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {



        if($this->is_after && $this->is_after !== '') { $this->merge(['is_after' => ThingUtilities::boolishToBool(val: $this->is_after)]);}
        if($this->is_manual && $this->is_manual !== '') { $this->merge(['is_manual' => ThingUtilities::boolishToBool(val: $this->is_manual)]);}
        if($this->is_blocking && $this->is_blocking !== '') { $this->merge(['is_blocking' => ThingUtilities::boolishToBool(val: $this->is_blocking)]);}
        if($this->is_sharing && $this->is_sharing !== '') { $this->merge(['is_sharing' => ThingUtilities::boolishToBool(val: $this->is_sharing)]);}
        if($this->is_manual_notice && $this->is_manual_notice !== '') { $this->merge(['is_manual_notice' => ThingUtilities::boolishToBool(val: $this->is_manual_notice)]);}
    }
    /**
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        return [

            'uuid' => ['uuid', 'nullable'],
            'hook_uuid' => ['uuid', 'nullable'],
            'thing_uuid' => ['uuid', 'nullable'],
            'error_uuid' => ['uuid', 'nullable'],
            'alert_uuid' => ['uuid', 'nullable'],


            'code_range_min' => ['nullable', Rule::numeric()->min(0)->max(599)->integer()],
            'code_range_max' => ['nullable', Rule::numeric()->min(0)->max(599)->integer()],
            'is_manual' => ['nullable', 'boolean'],
            'is_blocking' => ['nullable', 'boolean'],
            'is_after' => ['nullable', 'boolean'],
            'is_manual_notice' => ['nullable', 'boolean'],
            'is_sharing' => ['nullable', 'boolean'],
            'status' => ['nullable',Rule::enum(TypeOfCallbackStatus::class)],
            'hook_callback_type' => ['nullable',Rule::enum(TypeOfCallback::class)],

            'ran_at_min' => ['date', 'nullable'],
            'ran_at_max' => ['date', 'nullable'],
            'created_at_min' => ['date', 'nullable'],
            'created_at_max' => ['date', 'nullable'],



        ];
    }
}
