<?php

namespace Pomm\Object;

use \Pomm\Exception\Exception as PommException;

class RowStructure
{
    protected $field_definitions;

    public function __construct(Array $field_definitions = array())
    {
        $this->field_definitions = $field_definitions;
    }

    public function addField($name, $type)
    {
        if ($this->hasField($name))
        {
            throw new PommException(sprintf('Field "%s" already set in class "%s".', $name, get_class($this)));
        }

        $this->field_definitions[$name] = $type;
    }

    public function getFieldNames()
    {
        return array_keys($this->field_definitions);
    }

    public function hasField($name)
    {
        return array_key_exists($name, $this->field_definitions);
    }

    public function getTypeFor($name)
    {
        return $this->field_definitions[$name];
    }

    public function getDefinition()
    {
        return $this->field_definitions;
    }
}
