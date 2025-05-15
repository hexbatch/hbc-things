<?php


namespace Hexbatch\Things\Requests;


use Hexbatch\Things\Enums\TypeOfCallbackStatus;
use Hexbatch\Things\Enums\TypeOfCallback;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class CallbackSearchRequest extends FormRequest
{


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
