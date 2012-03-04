<?php

namespace Pomm\Identity;

class IdentityMapperNone implements IdentityMapperInterface
{
    /**
     * @see Pomm\Identity\IdentityMapperInterface.
     **/
    public function getModelInstance(\Pomm\Object\BaseObject $object, Array $pk_fields)
    {
        return $object;
    }

    /**
     * @see Pomm\Identity\IdentityMapperInterface.
     **/
    public function checkModelInstance($class_name, Array $primary_key)
    {
        return false;
    }

    /**
     * @see Pomm\Identity\IdentityMapperInterface.
     **/
    public function discardInstance($class_name, Array $primary_key)
    {
    }
}
