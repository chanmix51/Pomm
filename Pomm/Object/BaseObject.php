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
abstract class BaseObject implements \ArrayAccess
{
    const NONE     = 0;
    const EXIST    = 1;
    const MODIFIED = 2;


    protected $fields = array();
    protected $fields_definition = array();
    protected $status = 0;
    protected $primary_key = array();

    /**
     * __construct
     * The constructor. This shouldn't be called directly, see BaseObjectMap::createObject() instead
     *
     * @param Array $pk the primary key definition
     * @param Array $fields_definition the fields declared to be stored in the database
     * @access public
     * @return void
     */
    public function __construct(Array $pk = array(), Array $fields_definition = array())
    {
        $this->setPrimaryKey($pk);
        $this->fields_definition = $fields_definition;
    }

    /**
     * get
     * Returns the $name value
     *
     * @param string $var The key you want to retrieve value from
     * @access public
     * @return mixed
     */
    public function get($var)
    {
        if ($this->has($var)) {
            return $this->fields[$var];
        }
    }

    /**
     * has
     * Returns true if the given key exists
     *
     * @param string $var
     * @access public
     * @return boolean
     */
    public function has($var)
    {
        return array_key_exists($var, $this->fields);
    }

    /**
     * __call
     * Allows dynamic methods getXXX, setXXX or addXXX
     *
     * @param mixed $method
     * @param mixed $arguments
     * @access public
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        $operation = substr(strtolower($method), 0, 3);
        $attribute = sfInflector::underscore(substr($method, 3));

        switch($operation)
        {
        case 'set':
            return $this->set($attribute, $arguments[0]);
        case 'get':
            return $this->get($attribute);
        case 'add':
            return $this->add($attribute, $arguments[0]);
        default:
            throw new Exception(sprintf('No such method "%s:%s()"', get_class($this), $method));
        }
    }

    /**
     * hydrate
     * Merge internal values with given $values in the object
     *
     * @param Array $values
     * @access public
     * @return void
     */
    public final function hydrate(Array $values)
    {
        $this->fields = array_merge($this->fields, $values);
    }

    /**
     * convert
     * Make all keys lowercase and hydrate the object
     *
     * @param Array $values
     * @access public
     * @return void
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
     * Returns the current status of the instance
     * can be self::NONE, self::EXIST and SELF::MODIFIED
     *
     * @access public
     * @return integer
     */
    public function _getStatus()
    {
        return $this->status;
    }

    /**
     * _setStatus
     * Forces the status of the object
     *
     * @param integer $status
     * @access public
     * @return void
     */
    public function _setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * extract
     * Returns the fields array
     *
     * @access public
     * @return array
     */
    public function extract()
    {
        return $this->fields;
    }

    /**
     * setPrimaryKey
     *
     * @param Array $keys
     * @access public
     * @return void
     */
    public function setPrimaryKey(Array $keys)
    {
        $this->primary_key = $keys;
    }

    /**
     * getPrimaryKey
     * returns the values of the instance's primary key
     *
     * @access public
     * @return void
     */
    public function getPrimaryKey()
    {
        $keys = array();
        foreach ($this->primary_key as $key)
        {
            $keys[$key] = array_key_exists($key, $this->fields) ? $this->fields[$key] : null;
        }

        return $keys;
    }

    /**
     * __set
     * PHP magic to set attributes
     *
     * @param string $var
     * @param mixed $value
     * @access public
     * @return void
     */
    public function __set($var, $value)
    {
        $this->set($var, $value);
    }
    /**
     * __get
     * PHP magic to get attributes
     *
     * @param string $var
     * @access public
     * @return void
     */
    public function __get($var)
    {
        $this->get($var);
    }

    /**
     * set
     * Set a value in the varholder
     *
     * @param string $var
     * @param mixed $value
     * @access public
     * @return void
     */
    public function set($var, $value)
    {
        $this->fields[$var] = $value;
        $this->status = $this->status | self::MODIFIED;
    }

    /**
     * add
     * When the corresponding attribute is an array, call this method
     * to set values
     *
     * @param string $var
     * @param mixed $value
     * @access public
     * @return void
     */
    public function add($var, $value)
    {
        if (preg_match('/\[\]$/', $this->fields_definition[$var]))
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
        else
        {
            throw new Exception(sprintf("'%s' field is not defined as an array ('%s').", $var, $this->fields_definition[$var]));
        }
    }

    /**
     * isNew
     * is the current object self::NEW (does not it exist in the database already ?)
     *
     * @access public
     * @return boolean
     */
    public function isNew()
    {
        return ! $this->status & self::EXIST;
    }

    /**
     * isModified
     * Has the object been modified since we know it ?
     *
     * @access public
     * @return boolean
     */
    public function isModified()
    {
        return $this->status & self::MODIFIED;
    }

    /**
     * offsetExists
     * @see ArrayAccess
     **/
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * offsetSet
     * @see ArrayAccess
     **/
    public function offsetSet($offset, $value)
    {
        $method_name = "set".sfInflector::camelize($offset);
        $this->$method_name($value);
    }

    /**
     * offsetGet
     * @see ArrayAccess
     **/
    public function offsetGet($offset)
    {
        $method_name = "get".sfInflector::camelize($offset);

        return $this->$method_name();
    }

    /**
     * offsetUnset
     * @see ArrayAccess
     **/
    public function offsetUnset($offset)
    {
        $this->offsetSet($offset, null);
    }
}
