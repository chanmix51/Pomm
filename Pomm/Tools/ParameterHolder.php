<?php

namespace Pomm\Tools;

use Pomm\Exception\Exception;

/**
 * Pomm\Tools\ParameterHolder
 *
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class ParameterHolder implements \ArrayAccess, \Iterator
{
    protected $parameters;

    /**
     * __construct()
     *
     * @param Array $parameters (optional)
     **/
    public function __construct(Array $parameters = array())
    {
        $this->parameters = $parameters;
    }

    /**
     * setParameter
     *
     * Set a parameter.
     *
     * @param String $name
     * @param String $value
     * */
    public function setParameter($name, $value)
    {
        $this->parameters[$name] = $value;
    }

    /**
     * hasParameter
     *
     * check if parameter exists.
     *
     * @param String $name
     * @return Boolean
     * */
    public function hasParameter($name)
    {
        return array_key_exists($name, $this->parameters);
    }

    /**
     * getParameter
     *
     * Returns the parameter "name" or "default" if not set.
     *
     * @access public
     * @param String $name
     * @param String $default Optionnal default value if name not set.
     * @return String Parameter's value or default.
     **/
    public function getParameter($name, $default = null)
    {
        return $this->hasParameter($name) ? $this->parameters[$name] : $default;
    }

    /**
     * getParameters()
     *
     * Return the parameters as array
     * @return Array the parameterrs
     **/
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * mustHave()
     *
     * Throw an exception if a param is not set
     * @access public
     * @param String $name the parameter's name
     **/
    public function mustHave($name)
    {
        if (!$this->hasParameter($name)) {
            throw new Exception(sprintf('The parameters "%s" is mandatory.', $name));
        }
    }

    /**
     * setDefaultValue()
     *
     * Sets a default value if the param $name is not set
     * @access public
     * @param String $name the parameter's name
     * @param mixed $value the default value
     **/
    public function setDefaultValue($name, $value)
    {
        if (!$this->hasParameter($name)) {
            $this->setParameter($name, $value);
        }
    }

    /**
     * mustBeOneOf()
     *
     * Check if the given parameter is one of the values passed as argument. If
     * not, an exception is thrown.
     *
     * @param String $name the parameter's name
     * @param Array $value;
     * @return Boolean (true)
     **/

    public function mustBeOneOf($name, Array $values)
    {
        if (!in_array($this[$name], $values)) {
            throw new Exception(sprintf('The parameters "%s" must be one of [%s].', $name, implode(', ', $values)));
        }

        return true;
    }

    /**
     * unsetParameter()
     *
     * @param String $name
     **/
    public function unsetParameter($name)
    {
        unset($this->parameter[$name]);
    }

    /**
     * offsetExists()
     * @see ArrayAccess
     **/
    public function offsetExists($name)
    {
        return $this->hasParameter($name);
    }

    /**
     * offsetGet()
     * @see ArrayAccess
     **/
    public function offsetGet($name)
    {
        return $this->getParameter($name);
    }

    /**
     * offsetSet()
     * @see ArrayAccess
     **/
    public function offsetSet($name, $value)
    {
        $this->setParameter($name, $value);
    }

    /**
     * offsetUnset()
     * @see ArrayAccess
     **/
    public function offsetUnset($name)
    {
        $this->unsetParameter($name);
    }

    /**
     * current()
     * @see Iterator
     **/
    public function current()
    {
        return current($this->parameters);
    }

    /**
     * next()
     * @see Iterator
     **/
    public function next()
    {
        return next($this->parameters);
    }

    /**
     * key()
     * @see Iterator
     **/
    public function key()
    {
        return key($this->parameters);
    }

    /**
     * valid()
     * @see Iterator
     **/
    public function valid()
    {
        return valid($this->parameters);
    }

    /**
     * rewind()
     * @see Iterator
     **/
    public function rewind()
    {
        rewind($this->parameters);
    }
}
