<?php

namespace Pomm\Identity;

use Pomm\Object\BaseObject;

class IdentityMapperNone implements IdentityMapperInterface
{
    /**
     * {@inheritdoc}
     */
    public function getInstance(BaseObject $object, array $pk_fields)
    {
        return $object;
    }
    /**
     * {@inheritdoc}
     */
    public function flush()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function clear(BaseObject $object, array $pk_fields)
    {
    }
}
