<?php

namespace Pomm\Identity;

interface IdentityMapperInterface
{
    /**
     * getInstance
     *
     * Sets the given model instance to de data mapper if not
     * set already. Return the set instance.
     *
     * @param \Pomm\Object\BaseObject    $object     Entity instance.
     * @param Array                     $pk_fields  Unique identitfier for that entity.
     * @return \Pomm\Object\BaseObject
     **/
    public function getInstance(\Pomm\Object\BaseObject $object, Array $pk_fields);


    /**
     * clear
     *
     * Remove the instance from the IM if it exists.
     *
     * @param \Pomm\Object\BaseObject    $object     Entity instance.
     * @param Array                     $pk_fields  Unique identitfier for that entity.
     **/
    public function clear(\Pomm\Object\BaseObject $object, Array $pk_fields);

    /**
     * flush
     *
     * Flush the identity mapper.
     **/
    public function flush();
}
