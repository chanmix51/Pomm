<?php

namespace Pomm\Identity;

use Pomm\Object\BaseObject;

class IdentityMapperSmart extends IdentityMapperStrict
{
    /**
     * @see Pomm\Identity\IdentityMapperInterface.
     **/
    public function getModelInstance(BaseObject $object, Array $pk_fields)
    {
        if (count($pk_fields) == 0)
        {
            return $object;
        }

        $crc = $this->getSignature(get_class($object), $object->get($pk_fields));

        if (array_key_exists($crc, $this->mapper))
        {
            if (!$this->mapper[$crc]->_getStatus() & BaseObject::EXIST)
            {
                $this->mapper[$crc] = $object;
            }
            elseif (!$this->mapper[$crc]->isModified())
            {
                $this->mapper[$crc]->hydrate($object->extract());
            }
        }
        else
        {
            $this->mapper[$crc] = $object;
        }

        return $this->mapper[$crc];
    }

    /**
     * @see Pomm\Identity\IdentityMapperInterface.
     **/
    public function checkModelInstance($class_name, Array $pk_fields)
    {
        if (count($pk_fields) == 0)
        {
            return $object;
        }

        $crc = $this->getSignature($class_name, $pk_fields);

        return array_key_exists($crc, $this->mapper) && ($this->mapper[$crc]->_getStatus() & BaseObject::EXIST)
            ? $this->mapper[$crc]
            : false
            ;
    }
}
