<?php

namespace Hexbatch\Things\Rules;

use Closure;
use Hexbatch\Things\Models\Thing;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateOwner implements ValidationRule,DataAwareRule
{

    /**
     * All the data under validation.
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    protected ?string $owner_type = null;
    protected ?int $owner_id = null;

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        if ($this->owner_id && $this->owner_type) {

            if (!Thing::isRegisteredOwner(owner_type: $this->owner_type,owner_id: $this->owner_id) ) {
                $fail(sprintf("%s %s is not a registered thing owner",$this->owner_type,$this->owner_id));
            }
        } else if ($this->owner_type) {
            if (!Thing::isRegisteredOwnerType(owner_type: $this->owner_type) ) {
                $fail(sprintf("%s is not a registered thing owner type",$this->owner_type));
            }
        }
        else if ($this->owner_id) {
            $fail(sprintf("Cannot have only an owner id of %s without a type",$this->owner_id));
        }
    }


    public function setData(array $data)
    {
        $this->data = $data;
        if (!empty($data['owner_type'])) {
            $this->owner_type = (string)$data['owner_type'];
        }
        if (!empty($data['owner_type_id'])) {
            $this->owner_id = (int)$data['owner_type_id'];
        }
        return $this;
    }
}
