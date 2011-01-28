<?php
namespace Pomm\Query;

use Pomm\Exception\Exception;

abstract class Query
{
    protected $where;
    protected $from;
    protected $fields = array();

    abstract public function __toString();

    public function __construct($from)
    {
        $this->setFrom($from);
    }

    public function setFrom($from)
    {
        $this->from = $from;
    }

    public function getFrom()
    {
        return $this->from;
    }

    /**
     * hasField
     *
     * returns the index of the field if it exists or false if not
     * @access public
     * @param String name the name of the field
     * @return mixed index of the field or false
     **/
    public function hasField($name)
    {
        if (in_array($name, $this->fields))
        {
            $flip = array_flip($this->fields);
            return $flip[$name];
        }

        return false;
    }

    /**
     * addField
     *
     * Add a field in the fields array. If the position is < 0, the field is 
     * inserted from the end (-1).
     *
     * @access public
     * @param $name String the field's name
     * @param $pos Integer field position (optionnal) default -1
     * @return void
     **/
    public function addField($name, $pos = -1)
    {
        if ($pos < 0)
        {
            $pos = count($this->fields) + $pos + 1;
        }

        $this->insertField($name, $pos);
    }

    /**
     * insertField
     *
     * Inserts an element at given position in the fields array. If the position is negative an Exception 
     * is thrown.
     *
     * @access public
     * @param $name String the name of the field
     * @param $pos Integer the position of the field
     * @return void
     **/
    public function insertField($name, $pos)
    {
        if (gettype($pos) != 'integer' or (integer)$pos < 0)
        {
            throw new Exception(sprintf("Position must be a positive integer, type '%s' with value '%s' was given.", gettype($pos), $pos));
        }

        if ($pos >= count($this->fields))
        {
            array_push($name, $fields);
            return;
        }

        $fields = array();
        for ($index = 0; $index < count($this->fields); $fields++)
        {
            if ($index >= $pos)
            {
                $fields[$index] = $name;
            }
            $fields[] = $name;
        }

        $this->fields = $fields;
    }
}
