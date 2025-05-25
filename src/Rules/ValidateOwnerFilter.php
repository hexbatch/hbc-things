<?php

namespace Hexbatch\Things\Rules;


class ValidateOwnerFilter extends ValidateOwner
{


    public function setData(array $data)
    {
        $this->setDataInternal(data: $data,owner_key: 'filter_owner_type',id_key: 'filter_owner_id');
        return $this;
    }

}
