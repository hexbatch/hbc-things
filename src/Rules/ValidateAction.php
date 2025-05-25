<?php

namespace Hexbatch\Things\Rules;

use Closure;
use Hexbatch\Things\Models\Thing;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Ramsey\Uuid\Uuid;

class ValidateAction implements ValidationRule,DataAwareRule
{

    /**
     * All the data under validation.
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    protected ?string $action_type = null;
    protected ?int $action_id = null;
    protected ?string $action_uuid = null;

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        if (($this->action_id || $this->action_uuid) && $this->action_type) {

            if (!Thing::isRegisteredAction(action_type: $this->action_type,action_id: $this->action_id,uuid: $this->action_uuid) ) {
                $fail(sprintf("%s %s is not a registered thing action",$this->action_type,$this->action_id?: $this->action_uuid));
            }
        } else if ($this->action_type) {
            if (!Thing::isRegisteredActionType(action_type: $this->action_type) ) {
                $fail(sprintf("%s is not a registered thing action type",$this->action_type));
            }
        }
        else if ($this->action_id) {
            $fail(sprintf("Cannot have only an action id of %s without a type",$this->action_id));
        }
    }


    public function setData(array $data)
    {
        $this->data = $data;
        if (!empty($data['action_type'])) {
            $this->action_type = (string)$data['action_type'];
        }
        if (!empty($data['action_type_id'])) {
            $test =  (string)$data['action_type_id'];
            if (Uuid::isValid($test)) {
                $this->action_uuid = $test;
                $this->action_id = null;
            } else {
                $this->action_uuid = null;
                $this->action_id = $test;
            }
        }
        return $this;
    }
}
