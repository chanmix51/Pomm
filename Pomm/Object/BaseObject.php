<?php

namespace Pomm\Object;

use Pomm\Exception\Exception;
use Pomm\External\sfInflector;

/**
 * BaseObject - Parent for entity classes
 *
 * @abstract
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT
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
     * Instanciate the entity and hydrate it with the given values.
     *
     * @param Array $values Optional starting values.
     **/
    public function __construct(Array $values = null)
    {
        if (!is_null($values))
        {
            $this->hydrate($values);
        }
    }
    /**
     * get
     *
     * Returns the $name value
     *
     * @final
     * @param String $var      Key you want to retrieve value from.
     * @param String $default  Default value if var does not exist.
     * @return mixed
     */
    public final function get($var)
    {
        if (is_scalar($var))
        {
            if ($this->has($var)) 
            {
                return $this->fields[$var];
            }
            else
            {
                throw new Exception(sprintf("No such key '%s'.", $var));
            }
        }
        elseif (is_array($var))
        {
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
    public final function has($var)
    {
        return array_key_exists($var, $this->fields) 
            || method_exists($this, sprintf("get%s", sfInflector::camelize($var)));
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
        list($operation, $attribute) = preg_split('/(?=[A-Z])/', $method, 2);
        $attribute = sfInflector::underscore($attribute);

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
            throw new Exception(sprintf('No such method "%s:%s()"', get_class($this), $method));
        }
    }

    /**
     * hydrate
     *
     * Merge internal values with given $values in the object.
     *
     * @param Array $values
     */
    public final function hydrate(Array $values)
    {
        $this->fields = array_merge($this->fields, $values);
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
        foreach ($values as $key => $values)
        {
            $tmp[strtolower($key)] = $values;
        }

        $this->hydrate($tmp);
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
     * Returns the fields array.
     *
     * @return Array
     */
    public function extract()
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
        $method_name = "set".sfInflector::camelize($var);
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
        $method_name = "get".sfInflector::camelize($var);

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
    public final function set($var, $value)
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
        if ($this->has($var) && is_array($this->fields[$var]))
        {
            $this->fields[$var][] = $value;
        }
        else
        {
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
     **/
    public final function clear($offset)
    {
        if ($this->has($offset))
        {
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
        return (boolean) (! $this->status & self::EXIST);
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
     * offsetExists
     * @see ArrayAccess
     **/
    public function offsetExists($offset)
    {
        $method_name = "has".sfInflector::camelize($offset);

        return $this->$method_name();
    }

    /**
     * offsetSet
     * @see ArrayAccess
     **/
    public function offsetSet($offset, $value)
    {
        $this->__set($offset, $value);
    }

    /**
     * offsetGet
     * @see ArrayAccess
     **/
    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    /**
     * offsetUnset
     * @see ArrayAccess
     **/
    public function offsetUnset($offset)
    {
        $this->clear($offset);
    }

    /**
     * getIterator
     * @see IteratorAggregate
     **/
    public function getIterator()
    {
        return new \ArrayIterator($this->fields);
    }
}
