<?php

namespace Pomm\Identity;

use Pomm\Object\BaseObject;

interface IdentityMapperInterface
{
    /**
     * getInstance
     *
     * Sets the given model instance to the data mapper if not
     * set already. Return the set instance.
     *
     * @param BaseObject $object    Entity instance.
     * @param array      $pk_fields Unique identifier for that entity.
     * @return BaseObject
     */
    public function getInstance(BaseObject $object, array $pk_fields);


    /**
     * clear
     *
     * Remove the instance from the IM if it exists.
     *
     * @param BaseObject $object Entity instance.
     * @param array $pk_fields  Unique identifier for that entity.
     */
    public function clear(BaseObject $object, array $pk_fields);

    /**
     * flush
     *
     * Flush the identity mapper.
     */
    public function flush();
}
