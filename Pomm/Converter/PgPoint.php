<?php

namespace Pomm\Converter;

use Pomm\Converter\ConverterInterface;
use Pomm\Type\Point;
use Pomm\Exception\Exception;

/**
 * Pomm\Converter\PgPoint - Geometric Point converter
 *
 * @package Pomm
 * @version $id$
 * @copyright 2011 - 2013 Grégoire HUBERT
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
     */
    public function __construct($class_name = 'Pomm\Type\Point')
    {
        $this->class_name = $class_name;
    }

    /**
     * @see Pomm\Converter\ConverterInterface
     */
    public function fromPg($data, $type = null)
    {
        if (!preg_match('/([0-9e\-+\.]+,[0-9e\-+\.]+)/', $data))
        {
            if ($data === null or $data === '')
            {
                return null;
            }

            throw new Exception(sprintf("Bad point representation '%s' (asked type '%s').", $data, $type));
        }

        list($x, $y) = preg_split("/,/", trim($data, "()"));

        return new $this->class_name($x, $y);
    }

    /**
     * @see Pomm\Converter\ConverterInterface
     */
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

        return sprintf("point(%.9e, %.9e)", $data->x, $data->y);
    }
}
