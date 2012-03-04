<?php

namespace Pomm\Identity;

class IdentityMapperStrict implements IdentityMapperInterface
{
    protected $mapper = array();

    /**
     * getSignature
     *
     * Return a unique identifier for each instance.
     *
     * @access protected
     * @param  String $class_name  Entity class name.
     * @param  Array  $primary_key Primary key.
     * @return Integer
     **/
    protected function getSignature($class_name, $primary_key)
    {
        if (count($primary_key) == 0)
        {
            throw new Exception(sprintf("Primary Key can not be empty when generating instance signature (class '%s').", $class_name));
        }

        return (int) hexdec(substr(sha1($class_name.join('', $primary_key)), 0, 6));
    }

    /**
     * @see Pomm\Identity\IdentityMapperInterface.
     **/
    public function getModelInstance(\Pomm\Object\BaseObject $object, Array $pk_fields)
    {
        if (count($pk_fields) == 0)
        {
            return $object;
        }

        $index = $this->getSignature(get_class($object), $object->get($pk_fields));

        if (!array_key_exists($index, $this->mapper))
        {
            $this->mapper[$index] = $object;
        }

        return $this->mapper[$index];
    }

    /**
     * @see Pomm\Identity\IdentityMapperInterface.
     **/
    public function checkModelInstance($class_name, Array $primary_key)
    {
        if (count($primary_key) == 0)
        {
            return $object;
        }

        $index = $this->getSignature($class_name, $primary_key);

        return array_key_exists($index, $this->mapper)
            ? $this->mapper[$index]
            : false;
    }

    /**
     * @see Pomm\Identity\IdentityMapperInterface.
     **/
    public function discardInstance($class_name, Array $primary_key)
    {
        if (count($primary_key) != 0)
        {
            unset($this->mapper[$this->getSignature($class_name, $primary_key)]);
        }
    }
}
