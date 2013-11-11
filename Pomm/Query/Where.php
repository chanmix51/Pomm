<?php

namespace Pomm\Query;

/**
 * Where
 *
 * This class represents a WHERE clause of a SQL statement. It deals with AND &
 * OR operator you can add using handy methods. This allows you to build
 * queries dynamically.
 *
 * @package Pomm
 * @version $id$
 * @copyright 2011 - 2013 Grégoire HUBERT
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
     * A constructor you can chain from.
     *
     * @static
     * @param String $element Optional logical element.
     * @param Array  $values  Optional elements' values.
     * @return Where
     */
    public static function create($element = null, Array $values = array())
    {
        return new self($element, $values);
    }

    /**
     * createWhereIn
     *
     * Create an escaped IN clause.
     *
     * @static
     * @param String $element
     * @param Array  $values
     * @return Where
     */
    public static function createWhereIn($element, Array $values)
    {
        $escape = function ($values) use (&$escape)
            {
                $escaped_values = array();
                foreach($values as $value)
                {
                    if (is_array($value))
                    {
                        $escaped_values[] = sprintf("(%s)", join(', ', $escape($value)));
                    }
                    else
                    {
                        $escaped_values[] = '$*';
                    }
                }

                return $escaped_values;
            };

        $get_values = function($values)
            {
                $array = array();

                foreach(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($values)) as $value)
                {
                    $array[] = $value;
                }

                return $array;

            };

        return new self(sprintf("%s IN (%s)", $element, join(", ", $escape($values))), $get_values($values));
    }

    /**
     * __construct
     *
     * @param String $element  (optional)
     * @param Array  $values   (optional)
     */
    public function __construct($element = null, Array $values = array())
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
     * or something else (XOR maybe).
     *
     * @param String $operator
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
     * @return Boolean
     */
    public function isEmpty()
    {
        return (is_null($this->element) and count($this->stack) == 0);
    }

    /**
     * transmute
     *
     * Absorbing another Where instance.
     *
     * @param Where $where
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
     * You can add a new WHERE clause with your own operator.
     *
     * @param Mixed  $element
     * @param Array  $values
     * @param String $operator
     * @return Where
     */
    public function addWhere($element, Array $values, $operator)
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
     * Or use a ready to use AND where clause.
     *
     * @param Mixed $element
     * @param Array $values
     * @return Where
     */
    public function andWhere($element, Array $values = array())
    {
        return $this->addWhere($element, $values, 'AND');
    }

    /**
     * orWhere
     *
     * @param Mixed $element
     * @param Array $values
     * @return Where
     */
    public function orWhere($element, Array $values = array())
    {
        return $this->addWhere($element, $values, 'OR');
    }

    /**
     * setStack
     *
     * @param Array $stack
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
     * where your SQL statement is built.
     *
     * @return String
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
     * @return Boolean
     */
    public function hasElement()
    {
        return ! is_null($this->element);
    }

    /**
     * getElement
     *
     * @return String
     */
    public function getElement()
    {
        return $this->element;
    }

    /**
     * parse
     *
     * @access protected
     * @return String
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
     * Get all the values back for the prepared statement.
     *
     * @return Array
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
