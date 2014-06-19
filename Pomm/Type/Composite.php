<?php

namespace Pomm\Type;

use \Pomm\Exception\Exception as PommException;

/**
 * Pomm\Type\Composite - Composite abstract type
 *
 * @package Pomm
 * @version $id$
 * @copyright 2011 - 2013 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
abstract class Composite
{
    /**
     * __construct
     *
     * This method is meant to be inherited. It takes the keys of the array and
     * look for a property with the same name. If no property is found, an
     * exception is raised.
     *
     * @param Array properties
     */
    public function __construct(Array $values)
    {
        foreach ($values as $name => $value) {
            if (!property_exists($this, $name)) {
                throw new PommException(sprintf("Composite type '%s' does not have a '%s' attribute.", get_class($this), $name));
            }

            $this->$name = $value;
        }
    }
}
