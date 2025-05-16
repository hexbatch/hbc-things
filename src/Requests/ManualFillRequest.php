<?php


namespace Hexbatch\Things\Requests;


use Hexbatch\Things\Models\ThingHook;
use Hexbatch\Things\Helpers\ThingUtilities;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property null|string|array data
 */
class ManualFillRequest extends FormRequest
{


    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {

        $this->merge([
            'data' => ThingUtilities::getArray(source: $this->data),
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

            /** @uses \Hexbatch\Things\Models\ThingCallback::callback_incoming_data */
            'data' => ['nullable',Rule::array()],


            /** @uses ThingHook::hook_priority */
            'code' => ['nullable', Rule::numeric()->min(0)->max(599)->integer()],

        ];
    }
}
