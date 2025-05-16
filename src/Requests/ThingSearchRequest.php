<?php


namespace Hexbatch\Things\Requests;


use Hexbatch\Things\Enums\TypeOfThingStatus;
use Hexbatch\Things\Helpers\ThingUtilities;
use Hexbatch\Things\Rules\ValidateAction;
use Hexbatch\Things\Rules\ValidateOwner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property null|string|array tags
 * @property null|string|bool async
 * @property null|string|bool is_root
 */

class ThingSearchRequest extends FormRequest
{


    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {

        $this->merge([
            'tags' => ThingUtilities::getArray(source: $this->tags),
        ]);

        if($this->async && $this->async !== '') { $this->merge(['async' => ThingUtilities::boolishToBool(val: $this->async)]);}
        if($this->is_root && $this->is_root !== '') { $this->merge(['is_root' => ThingUtilities::boolishToBool(val: $this->is_root)]);}

    }

    /**
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        return [

            'uuid' => ['uuid', 'nullable'],
            'error_uuid' => ['uuid', 'nullable'],
            'async' => ['nullable', 'boolean'],
            'is_root' => ['nullable', 'boolean'],

            'status' => ['nullable',Rule::enum(TypeOfThingStatus::class)],

            'ran_at_min' => ['date', 'nullable'],
            'ran_at_max' => ['date', 'nullable'],

            'started_at_min' => ['date', 'nullable'],
            'started_at_max' => ['date', 'nullable'],

            'created_at_min' => ['date', 'nullable'],
            'created_at_max' => ['date', 'nullable'],


            'action_id' => ['integer', 'nullable', new ValidateAction],

            'action_type' => ['string', 'nullable', new ValidateAction],

            'owner_id' => ['integer', 'nullable', new ValidateOwner],

            'owner_type' => ['string', 'nullable', new ValidateOwner],



            'tags' => ['nullable',Rule::array()],
            "tags.*" => "required|string|distinct|min:1",



        ];
    }
}
