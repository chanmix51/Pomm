<?php

namespace Pomm\Converter;

use Pomm\Converter\ConverterInterface;
use Pomm\Type\Point;

/**
 * Pomm\Converter\PgPoint - Geometric Point converter
 * 
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class PgPoint implements ConverterInterface
{
    protected $class_name;

    /**
     * __construct()
     *
     * @param String            $class_name      Optional fully qualified Point type class name.
     **/
    public function __construct($class_name = 'Pomm\Type\Point')
    {
        $this->class_name = $class_name;
    }

    /**
     * @see Pomm\Converter\ConverterInterface
     **/
    public function fromPg($data, $type = null)
    {
        $data = trim($data, "()");
        $values = preg_split("/,/", $data);

        return new $this->class_name($values[0], $values[1]);
    }

    /**
     * @see Pomm\Converter\ConverterInterface
     **/
    public function toPg($data, $type = null)
    {
        if (! $data instanceof $this->class_name)
        {
            if (!is_object($data)) 
            {
                $type = gettype($data);
            }
            else 
            {
                $type = get_class($data);
            }

            throw new Exception(sprintf("Converter PgPoint needs data to be an instance of Pomm\\Type\\Point ('%s' given).", $type));
        }

        return sprintf("point(%f, %f)", $data->x, $data->y);
    }
}
