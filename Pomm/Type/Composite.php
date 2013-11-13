<?php

namespace Pomm\Type;

use \Pomm\Exception\Exception as PommException;

abstract class Composite
{
    public function __construct(Array $values)
    {
        foreach($values as $name => $value)
        {
            if (!property_exists($this, $name))
            {
                throw new PommException(sprintf("Composite type '%s' does not have a '%s' attribute.", get_class($this), $name));
            }

            $this->$name = $value;
        }
    }
}
