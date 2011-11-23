<?php

namespace Pomm\Identity;

class IdentityMapper implements IdentityMapperInterface
{
    protected $mapper = array();

    /**
     * getModelInstance
     *
     * @see IdentityMapperInterface
     **/
    public function getModelInstance(\Pomm\Object\BaseObject $object)
    {
        $crc = $this->signature(get_class($object), $object->getPrimaryKey());

        if (!array_key_exists($crc, $this->mapper))
        {
            $this->mapper[$crc] = $object;
        }

        return $this->mapper[$crc];
    }

    /**
     * getSignature
     *
     * Return a unique identifier for each instance
     *
     * @param String $class_name the class name of the Model
     * @param Array the primary key
     * @return String
     **/
    protected function getSignature($class_name, Array $primary_key)
    {
        return md5($class_name . join('', $primary_key));
    }

    /**
     * checkModelInstance
     * @see IdentityMapperInterface 
     **/
    public function checkModelInstance($class_name, Array $primary_key)
    {
        $crc = $this->getSignature($class_name, $primary_key);

        return array_key_exists($crc, $this->mapper)
            ? $this->mapper[$crc]
            : false
            ;
    }

}
