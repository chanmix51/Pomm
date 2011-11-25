<?php

namespace Pomm\Identity;

class IdentityMapperStrict implements IdentityMapperInterface
{
    protected $mapper = array();

    /**
     * getSignature
     *
     * Return a unique identifier for each instance
     *
     * @param String $class_name the class name of the Model
     * @param Array the primary key
     * @return String
     **/
    protected function getSignature($class_name, $primary_key)
    {
        return (int) hexdec(substr(sha1($class_name.join('', $primary_key)), 0, 6));
    }

    /**
     * getModelInstance
     *
     * @see IdentityMapperInterface
     **/
    public function getModelInstance(\Pomm\Object\BaseObject $object)
    {
        $index = $this->getSignature(get_class($object), $object->getPrimaryKey());

        if (!array_key_exists($index, $this->mapper))
        {
            $this->mapper[$index] = $object;
        }

        return $this->mapper[$index];
    }

    /**
     * checkModelInstance
     * @see IdentityMapperInterface 
     **/
    public function checkModelInstance($class_name, Array $primary_key)
    {
        $index = $this->getSignature($class_name, $primary_key);

        return array_key_exists($index, $this->mapper)
            ? $this->mapper[$index]
            : false;
    }

    /**
     * discardInstance
     *
     * @see IdentityMapperInterface
     **/
    public function discardInstance($class_name, Array $primary_key)
    {
        unset($this->mapper[$this->getSignature($class_name, $primary_key)]);
    }
}
