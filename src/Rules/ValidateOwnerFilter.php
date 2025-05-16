<?php

namespace Hexbatch\Things\Rules;

use Closure;
use Hexbatch\Things\Models\ThingHook;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateOwnerFilter implements ValidationRule,DataAwareRule
{

    /**
     * All the data under validation.
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    protected ?string $filter_owner_type = null;
    protected ?int $filter_owner_id = null;

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        if ($this->filter_owner_id && $this->filter_owner_type) {

            if (!ThingHook::isRegisteredOwner(owner_type: $this->filter_owner_type,owner_id: $this->filter_owner_id) ) {
                $fail(sprintf("%s %s is not a registered hook owner",$this->filter_owner_type,$this->filter_owner_id));
            }
        } else if ($this->filter_owner_type) {
            if (!ThingHook::isRegisteredOwnerType(owner_type: $this->filter_owner_type) ) {
                $fail(sprintf("%s is not a registered hook owner type",$this->filter_owner_type));
            }
        }
        else if ($this->filter_owner_id) {
            $fail(sprintf("Cannot have only an filter owner id of %s without a type",$this->filter_owner_id));
        }
    }


    public function setData(array $data)
    {
        $this->data = $data;
        if (!empty($data['filter_owner_type'])) {
            $this->filter_owner_type = (string)$data['filter_owner_type'];
        }
        if (!empty($data['filter_owner_id'])) {
            $this->filter_owner_id = (int)$data['filter_owner_id'];
        }
        return $this;
    }
}
