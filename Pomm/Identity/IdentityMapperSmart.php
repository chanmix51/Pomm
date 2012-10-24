<?php

namespace Pomm\Identity;

use Pomm\Object\BaseObject;

class IdentityMapperSmart extends IdentityMapperStrict
{
    /**
     * @see Pomm\Identity\IdentityMapperInterface.
     **/
    public function getInstance(BaseObject $object, Array $pk_fields)
    {
        if (count($pk_fields) == 0)
        {
            return $object;
        }

        $index = $this->getSignature($object, $object->get($pk_fields));

        if (array_key_exists($index, $this->mapper))
        {
            $this->mapper[$index]->hydrate(array_merge($object->getFields(), $this->mapper[$index]->getFields()));
        }
        else
        {
            $this->mapper[$index] = $object;
        }

        return $this->mapper[$index];
    }
}
