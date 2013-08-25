<?php

namespace Pomm\Identity;

use Pomm\Exception\Exception;
use Pomm\Object\BaseObject;

/**
 * Pomm\Identity\IdentityMapperStrict - "strict" identity mapper
 *
 * @package Pomm
 * @uses Pomm\Object\BaseObject
 * @uses Pomm\Exception\Exception
 * @version $id$
 * @copyright 2011 - 2013 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class IdentityMapperStrict implements IdentityMapperInterface
{
    protected $mapper = array();

    /**
     * getSignature
     *
     * Return a unique identifier for each instance.
     *
     * @access protected
     * @param  String $object      Entity class name.
     * @param  Array  $primary_key Primary key.
     * @return Integer
     */
    protected function getSignature($object, $primary_key)
    {
        $class_name = get_class($object);

        if (count($primary_key) == 0)
        {
            throw new Exception(sprintf("Primary Key can not be empty when generating instance signature (class '%s').", $class_name));
        }

        return (int) hexdec(substr(sha1($class_name.join('', $primary_key)), 0, 6));
    }

    /**
     * {@inheritdoc}
     */
    public function getInstance(BaseObject $object, array $pk_fields)
    {
        if (count($pk_fields) == 0)
        {
            return $object;
        }

        $index = $this->getSignature($object, $object->get($pk_fields));

        if (!array_key_exists($index, $this->mapper))
        {
            $this->mapper[$index] = $object;
        }

        return $this->mapper[$index];
    }

    /**
     * {@inheritdoc}
     */
    public function clear(BaseObject $object, array $pk_fields)
    {
        if (count($pk_fields) != 0)
        {
            $index = $this->getSignature($object, $object->get($pk_fields));
            unset($this->mapper[$index]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $this->mapper = array();
    }
}
