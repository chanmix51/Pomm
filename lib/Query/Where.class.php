<?php
namespace Pomm\Query;

/**
 * Where 
 * 
 * This class represents a WHERE clause of a SQL statement. It deals with AND & 
 * OR operator you can add using handy methods. This allows you to build 
 * queries dynamically.
 *
 * @package PommBundle
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class Where
{
  public $stack = array();
  public $element;
  public $values = array();
  public $operator;

  /**
   * create 
   *
   * A constructor you can chain from
   * 
   * @param string $element 
   * @param array $values 
   * @static
   * @access public
   * @return Where
   */
  public static function create($element = null, $values = array())
  {
    return new self($element, $values);
  }

  /**
   * __construct 
   * 
   * @param string $element 
   * @param array $values 
   * @access public
   * @return void
   */
  public function __construct($element = null, $values = array())
  {
    if (!is_null($element))
    {
      $this->element = $element;
      $this->values = $values;

    }
  }

  /**
   * setOperator 
   * 
   * is it an AND or an OR ?
   * or something else (XOR maybe)
   *
   * @param string $operator 
   * @access public
   * @return Where
   */
  public function setOperator($operator)
  {
    $this->operator = $operator;

    return $this;
  }

  /**
   * isEmpty 
   * 
   * is it a fresh brand new object ?
   *
   * @access public
   * @return boolean
   */
  public function isEmpty()
  {
    return (is_null($this->element) and count($this->stack) == 0);
  }

  /**
   * transmute 
   * 
   * @param Where $where 
   * @access public
   * @return void
   */
  public function transmute(Where $where)
  {
    $this->stack = $where->stack;
    $this->element = $where->element;
    $this->operator = $where->operator;
    $this->values = $where->values;
  }

  /**
   * addWhere 
   *
   * You can add a new WHERE clause with your own operator
   * 
   * @param string $element 
   * @param array $values 
   * @param string $operator 
   * @access public
   * @return Where
   */
  public function addWhere($element, $values, $operator)
  {
    if (!$element instanceof Where)
    {
      $element = new self($element, $values);
    }

    if ($element->isEmpty()) return $this;
    if ($this->isEmpty())
    {
      $this->transmute($element);

      return $this;
    }

    if ($this->hasElement())
    {
      $this->stack = array(new self($this->getElement(), $this->values), $element);
      $this->element = NULL;
      $this->values = array();
    }
    else
    {
      if ($this->operator == $operator)
      {
        $this->stack[] = $element;
      }
      else
      {
        $this->stack = array(self::create()->setStack($this->stack)->setOperator($this->operator), $element);
      }
    }

    $this->operator = $operator;

    return $this;
  }

  /**
   * andWhere 
   * 
   * Or use a ready to use AND where clause
   *
   * @param string $element 
   * @param array $values 
   * @access public
   * @return Where
   */
  public function andWhere($element, $values = array())
  {
     return $this->addWhere($element, $values, 'AND');
  }

  /**
   * orWhere 
   * 
   * OR where clause
   *
   * @param string $element 
   * @param array $values 
   * @access public
   * @return Where
   */
  public function orWhere($element, $values = array())
  {
    return $this->addWhere($element, $values, 'OR');
  }

  /**
   * setStack 
   * 
   * @param Array $stack 
   * @access public
   * @return Where
   */
  public function setStack(Array $stack)
  {
    $this->stack = $stack;

    return $this;
  }

  /**
   * __toString 
   * 
   * where your SQL statement is built
   *
   * @access public
   * @return string
   */
  public function __toString()
  {
    if ($this->isEmpty())
    {
      return 'true';
    }
    else
    {
      return $this->parse();
    }
  }

  /**
   * hasElement 
   * 
   * @access public
   * @return boolean
   */
  public function hasElement()
  {
    return ! is_null($this->element);
  }

  /**
   * getElement 
   * 
   * @access public
   * @return string
   */
  public function getElement()
  {
    return $this->element;
  }

  /**
   * parse 
   * 
   * @access protected
   * @return string
   */
  protected function parse()
  {
    if ($this->hasElement())
    {
      return $this->getElement();
    }

    $stack = array();
    foreach ($this->stack as $offset => $where)
    {
      $stack[$offset] = $where->parse();
    }

    return sprintf('(%s)', join(sprintf(' %s ', $this->operator), $stack));
  }

  /**
   * getValues 
   *
   * get all the values back for the prepated statement
   * 
   * @access public
   * @return void
   */
  public function getValues()
  {
    if ($this->isEmpty())
    {
      return array();
    }
    if ($this->hasElement())
    {
      return $this->values;
    }

    $values = array();
    foreach($this->stack as $offset => $where)
    {
      $values = array_merge($values, $where->getValues());
    }

    return $values;
  }
}
