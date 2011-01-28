<?php

namespace Pomm\Tools\ParameterHolder;

use Pomm\Exception\Exception;

class ParameterHolder implements \ArrayAccess, \Iterator
{
    protected $parameters;

    /**
     * __construct()
     * 
     * @access public
     * @param Array $parameters
     **/
    public function __construct(Array $parameters = array())
    {
        $this->parameters = $parameters;
    }

  /*
   * setParameter
   *
   * Set a parameter
   * @access public
   * @param string name the parameter's name
   * @param string value the parameter's value
   * @return void
   * */
  public function setParameter($name, $value) 
  {
      $this->parameters[$name] = $value;
  }

  /*
   * hasParameter
   *
   * check if parameter exists
   * @access public
   * @param string name the parameter's name
   * @return boolean
   * */
  public function hasParameter($name)
  {
      return array_key_exists($name, $this->parameters);
  }

  /*
   * getParameter
   *
   * Returns the parameter "name" or "default" if not set
   * @access public
   * @param string name the parameter's name
   * @param string default optionnal default value if name not set
   * @return string the parameter's value or default
   * */
  public function getParameter($name, $default = null)
  {
      return $this->hasParameter($name) ? $this->parameters[$name] : $default;
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
      if (!$this->hasParameter($name))
      {
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
      if (!$this->hasParameter($name))
      {
          $this->setParameter($name, $value);
      }
  }

  /**
   * unsetParameter()
   *
   * unset a parameter
   * @access public
   * @param String $name the parameter's name
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
