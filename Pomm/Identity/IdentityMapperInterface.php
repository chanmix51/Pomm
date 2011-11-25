<?php

namespace Pomm\Identity;

interface IdentityMapperInterface
{
    /**
     * getModelInstance
     *
     * Sets the given model instance to de data mapper if not
     * set already. Return the set instance.
     *
     * @param Pomm\Object\BaseObject
     * @return Pomm\Object\BaseObject
     **/
    public function getModelInstance(\Pomm\Object\BaseObject $object);


    /**
     * checkModelInstance
     *
     * Check if an instance is present in the mapper and returns it if yes
     * false if not
     *
     * @param String $class_name the class name of the Model
     * @param Array the primary key
     * @return mixed
     **/
    public function checkModelInstance($class_name, Array $primary_key);

    /**
     * discardInstance
     *
     * remove an existing model instance
     *
     * @param String $class_name the class name of the Model
     * @param Array the primary key
     **/
    public function discardInstance($class_name, Array $primary_key);
}
