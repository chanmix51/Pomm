<?php

namespace Pomm\Identity;

class IdentityMapperNone implements IdentityMapperInterface
{
    /**
     * @see \Pomm\Identity\IdentityMapperInterface.
     **/
    public function getInstance(\Pomm\Object\BaseObject $object, Array $pk_fields)
    {
        return $object;
    }
    /**
     * @see \Pomm\Identity\IdentityMapperInterface.
     **/
    public function flush()
    {
    }

    /**
     * @see \Pomm\Identity\IdentityMapperInterface.
     **/
    public function clear(\Pomm\Object\BaseObject $object, Array $pk_fields)
    {
    }
}
