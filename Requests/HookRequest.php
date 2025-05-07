<?php


use App\Models\TestActionDatum;
use App\Rules\ValidateActionInnardClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property int id
 * @property string|int|null test_action_invalid_offset_seconds
 * @property string|int|null test_action_start_offset_seconds
 * @property string|int|bool|null test_action_async
 * @property string|int|null test_action_priority
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
            'test_action_invalid_offset_seconds' => intval($this->test_action_invalid_offset_seconds),
            'test_action_start_offset_seconds' => intval($this->test_action_start_offset_seconds),
            'test_action_async' => !!$this->test_action_async,
            'test_action_priority' => intval($this->test_action_priority),
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
            'parent_action_id' => ['integer', 'nullable',Rule::exists(TestActionDatum::class,'id')->whereNot('id',$this->id)],
            "test_action_constant"    => "nullable|array",
            "test_action_tags"    => "nullable|array",
            "test_action_tags.*"  => "required|string|distinct|min:1",

            "test_action_data_row_limit"    => "integer",
            "test_action_priority"    => "integer",
            "test_action_invalid_offset_seconds"    => "integer",
            "test_action_start_offset_seconds"    => "integer",
            "test_action_async"    => "boolean",

            "parent_key"    => "nullable|string",
            "test_action_color"    => "nullable|string",
            "test_action_type"    => "nullable|string",
            "test_action_name"    => "nullable|string",


            "test_action_innard_class"    => ['required','string',new ValidateActionInnardClass],
        ];
    }
}
