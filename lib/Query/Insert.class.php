<?php

namespace Pomm\Query;

use Pomm\Exception\Exception;

class Insert extends Query
{
    protected $values;

    public function addValues(array $values)
    {
        $this->values = $values;
    }

    public function addValue($value)
    {
        $this->values[] = $value;
    }

    public function __toString()
    {
        return sprintf("INSERT INTO %s (%s) VALUES (%s)", $this->from, join(', ', $this->fields), join(', ', $this->values));
    }
}
