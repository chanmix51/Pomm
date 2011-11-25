<?php

namespace Pomm\Identity;

class IdentityMapperNone implements IdentityMapperInterface
{
    public function getModelInstance(\Pomm\Object\BaseObject $object)
    {
        return $object;
    }

    public function checkModelInstance($class_name, Array $primary_key)
    {
        return false;
    }

    public function discardInstance($class_name, Array $primary_key)
    {
    }
}

