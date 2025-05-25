<?php

namespace Hexbatch\Things\Rules;

use Closure;
use Hexbatch\Things\Models\Thing;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Ramsey\Uuid\Uuid;

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
    protected ?string $owner_uuid = null;
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        if (($this->owner_id || $this->owner_uuid) && $this->owner_type) {

            if (!Thing::isRegisteredOwner(owner_type: $this->owner_type,owner_id: $this->owner_id,owner_uuid: $this->owner_uuid) ) {
                $fail(sprintf("%s %s is not a registered thing owner",$this->owner_type,$this->owner_id?: $this->owner_uuid));
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
        $this->setDataInternal(data: $data,owner_key: 'owner_type',id_key: 'owner_type_id');
        return $this;
    }

    protected function setDataInternal(array $data,string $owner_key,string $id_key)
    {
        $this->data = $data;
        if (!empty($data[$owner_key])) {
            $this->owner_type = (string)$data[$owner_key];
        }
        if (!empty($data[$id_key])) {
            $test =  (string)$data[$id_key];
            if (Uuid::isValid($test)) {
                $this->owner_uuid = $test;
                $this->owner_id = null;
            } else {
                $this->owner_uuid = null;
                $this->owner_id = $test;
            }
        }
        return $this;
    }
}
