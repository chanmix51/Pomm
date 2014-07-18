<?php

namespace Pomm\Object;

use \Pomm\Exception\Exception as PommException;
use \Pomm\Tools\Inflector;

/**
 * BaseObject - Parent for entity classes
 *
 * @abstract
 * @uses Pomm\Exception\Exception
 * @uses Pomm\Tools\Inflector
 * @package Pomm
 * @version $id$
 * @copyright 2011 - 2013 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license MIT/X11 {@link http://opensource.org/licenses/mit-license.php}
 */
abstract class BaseObject implements \ArrayAccess, \IteratorAggregate
{
    const NONE     = 0;
    const EXIST    = 1;
    const MODIFIED = 2;

    protected $fields = array();
    protected $status = self::NONE;

    /**
     * __construct
     *
     * Instantiate the entity and hydrate it with the given values.
     *
     * @param Array $values Optional starting values.
     */
    public function __construct(Array $values = null)
    {
        if (!is_null($values)) {
            $this->hydrate($values);
        }
    }

    /**
     * get
     *
     * Returns the $var value
     *
     * @final
     * @param String $var      Key you want to retrieve value from.
     * @return mixed
     */
    final public function get($var)
    {
        if (is_scalar($var)) {
            if ($this->has($var)) {
                return $this->fields[$var];
            } else {
                throw new PommException(sprintf("No such key '%s'.", $var));
            }
        } elseif (is_array($var)) {
            return array_intersect_key($this->fields, array_flip($var));
        }
    }

    /**
     * has
     *
     * Returns true if the given key exists.
     *
     * @final
     * @param string $var
     * @return boolean
     */
    final public function has($var)
    {
        return array_key_exists($var, $this->fields);
    }

    /**
     * __call
     *
     * Allows dynamic methods getXxx, setXxx, hasXxx, addXxx or clearXxx.
     *
     * @param mixed $method
     * @param mixed $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        $split = preg_split('/(?=[A-Z])/', $method, 2);

        if (count($split) != 2) {
            throw new PommException(sprintf('No such method "%s:%s()"', get_class($this), $method));
        }

        $operation = $split[0];
        $attribute = Inflector::underscore($split[1]);

        switch($operation)
        {
            case 'set':
                return $this->set($attribute, $arguments[0]);
            case 'get':
                return $this->get($attribute);
            case 'add':
                return $this->add($attribute, $arguments[0]);
            case 'has':
                return $this->has($attribute);
            case 'clear':
                return $this->clear($attribute);
            default:
                throw new PommException(sprintf('No such method "%s:%s()"', get_class($this), $method));
        }
    }

    /**
     * hydrate
     *
     * Merge internal values with given $values in the object.
     *
     * @param Array $values
     */
    final public function hydrate(Array $values)
    {
        $this->fields = array_merge($this->fields, $values);

        return $this;
    }

    /**
     * convert
     *
     * Make all keys lowercase and hydrate the object.
     *
     * @param Array $values
     */
    public function convert(Array $values)
    {
        $tmp = array();
        foreach ($values as $key => $value) {
            $tmp[strtolower($key)] = $value;
        }

        return $this->hydrate($tmp);
    }

    /**
     * _getStatus
     *
     * Return the current status of the instance
     * can be self::NONE, self::EXIST and SELF::MODIFIED.
     *
     * @return integer
     */
    public function _getStatus()
    {
        return $this->status;
    }

    /**
     * _setStatus
     *
     * Force the status of the object.
     *
     * @param Integer $status
     */
    public function _setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * extract
     *
     * Returns the fields flatten as arrays.
     *
     * The complex stuff in here is when there is an array, since all elements
     * in arrays are the same type, we check only its first value to know if we need
     * to traverse it or not.
     *
     * @return Array
     */
    public function extract()
    {
        $array_recurse = function ($val) use (&$array_recurse) {
            if (is_scalar($val)) {
                return $val;
            }

            if (is_array($val)) {
                if (is_array(current($val)) || (is_object(current($val)) && current($val) instanceof BaseObject)) {
                    return array_map($array_recurse, $val);
                } else {
                    return $val;
                }
            }

            if (is_object($val) && $val instanceof BaseObject) {
                return $val->extract();
            }

            return $val;
        };

        return array_map($array_recurse, $this->fields);
    }

    /**
     * getFields
     *
     * Return the fields array.
     *
     * @return Array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * __set
     *
     * PHP magic to set attributes.
     *
     * @param String $var       Attribute name.
     * @param Mixed  $value     Attribute value.
     */
    public function __set($var, $value)
    {
        $method_name = "set".Inflector::camelize($var);
        $this->$method_name($value);
    }

    /**
     * __get
     *
     * PHP magic to get attributes.
     *
     * @param  String $var       Attribute name.
     * @return Mixed             Attribute value.
     */
    public function __get($var)
    {
        $method_name = "get".Inflector::camelize($var);

        return $this->$method_name();
    }

    /**
     * set
     *
     * Set a value in the varholder.
     *
     * @final
     * @param String $var       Attribute name.
     * @param Mixed  $value     Attribute value.
     */
    final public function set($var, $value)
    {
        $this->fields[$var] = $value;
        $this->status = $this->status | self::MODIFIED;
    }

    /**
     * add
     *
     * When the corresponding attribute is an array, call this method
     * to set values.
     *
     * @param string $var
     * @param mixed  $value
     */
    public function add($var, $value)
    {
        if ($this->has($var) && is_array($this->fields[$var])) {
            $this->fields[$var][] = $value;
        } else {
            $this->fields[$var] = array($value);
        }
    }

    /**
     * clear
     *
     * Drop an attribute from the varholder.
     *
     * @final
     * @param String $offset   Attribute name.
     */
    final public function clear($offset)
    {
        if ($this->has($offset)) {
            unset($this->fields[$offset]);
            $this->status = $this->status | self::MODIFIED;
        }
    }

    /**
     * isNew
     *
     * is the current object self::NEW (does not it exist in the database already ?).
     *
     * @return Boolean
     */
    public function isNew()
    {
        return (boolean) !($this->status & self::EXIST);
    }

    /**
     * isModified
     *
     * Has the object been modified since we know it ?
     *
     * @return Boolean
     */
    public function isModified()
    {
        return (boolean) ($this->status & self::MODIFIED);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        $method_name = "has".Inflector::camelize($offset);

        return $this->$method_name();
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->__set($offset, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        $this->clear($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->fields);
    }
}
