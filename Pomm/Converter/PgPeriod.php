<?php

namespace Pomm\Converter;

use Pomm\Converter\ConverterInterface;
use Pomm\Type\Period;

/**
 * Pomm\Converter\PgPeriod - Period converter
 * 
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class PgPeriod implements ConverterInterface
{
    protected $class_name;

    /**
     * __construct() - Converter constuctor
     *
     * @param String the fully qualified Period type class name
     **/
    public function __construct($class_name = 'Pomm\Type\Period')
    {
        $this->class_name = $class_name;
    }

    /**
     * @see ConverterInterface
     **/
    public function fromPg($data)
    {
        preg_match('/\[([^,]+), ([^,]+)\)/', $data, $matchs);

        return new $this->class_name(new \DateTime($matchs[1]), new \DateTime($matchs[2]));
    }

    /**
     * @see ConverterInterface
     **/
    public function toPg($data)
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

            throw new Exception(sprintf("Converter PgPeriod needs data to be an instance of Pomm\\Type\\Period ('%s' given).", $type));
        }

        return sprintf("period('%s', '%s')",
            $data->start->format("Y-m-d H:i:s.u"),
            $data->end->format("Y-m-d H:i:s.u")
        );
    }
}
