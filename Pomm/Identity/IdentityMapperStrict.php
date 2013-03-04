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
    protected function getSignature($object, $primary_key)
    {
        $class_name = get_class($object);

        if (count($primary_key) == 0) {
            throw new Exception(sprintf("Primary Key can not be empty when generating instance signature (class '%s').", $class_name));
        }

        return (int) hexdec(substr(sha1($class_name.join('', $primary_key)), 0, 6));
    }

    /**
     * @see Pomm\Identity\IdentityMapperInterface.
     **/
    public function getInstance(\Pomm\Object\BaseObject $object, Array $pk_fields)
    {
        if (count($pk_fields) == 0) {
            return $object;
        }

        $index = $this->getSignature($object, $object->get($pk_fields));

        if (!array_key_exists($index, $this->mapper)) {
            $this->mapper[$index] = $object;
        }

        return $this->mapper[$index];
    }

    /**
     * @see \Pomm\Identity\IdentityMapperInterface
     **/
    public function clear(\Pomm\Object\BaseObject $object, Array $pk_fields)
    {
        if (count($pk_fields) != 0) {
            $index = $this->getSignature($object, $object->get($pk_fields));
            unset($this->mapper[$index]);
        }
    }

    /**
     * @see \Pomm\Identity\IdentityMapperInterface
     **/
    public function flush()
    {
        $this->mapper = array();
    }
}
