<?php

namespace Pomm\Converter;

use Pomm\Converter\ConverterInterface;
use Pomm\Exception\Exception;
use Pomm\Type\RangeType;

/**
 * Pomm\Converter\PgNumberRange - Number range converter
 *
 * @package Pomm
 * @version $id$
 * @copyright 2011 - 2013 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class PgNumberRange implements ConverterInterface
{
    protected $class_name;

    /**
     * __construct()
     *
     * @param String            $class_name      Optional fully qualified TsRange type class name.
     */
    public function __construct($class_name = '\Pomm\Type\NumberRange')
    {
        $this->class_name = $class_name;
    }

    /**
     * @see Pomm\Converter\ConverterInterface
     */
    public function fromPg($data, $type = null)
    {
        if (!preg_match('/([\[\(])(-?[0-9\.]+),-?([0-9\.]+)([\]\)])/', $data, $matchs))
        {
            if ($data === null || $data === '')
            {
                return null;
            }

            throw new Exception(sprintf("Bad number range representation '%s' (asked type '%s').", $data, $type));
        }

        $options = $matchs[1] === '(' ? RangeType::EXCL_START : RangeType::INCL_BOUNDS;
        $options += $matchs[4] === ')' ? RangeType::EXCL_END : RangeType::INCL_BOUNDS;

        return new $this->class_name($matchs[2] + 0, $matchs[3] + 0, $options);
    }

    /**
     * @see Pomm\Converter\ConverterInterface
     */
    public function toPg($data, $type = null)
    {
        if (! $data instanceof \Pomm\Type\NumberRange )
        {
            throw new Exception(sprintf("PgNumberRange converter expects 'NumberRange' data to convert. '%s' given.", gettype($data)));
        }

        return sprintf("%s '%s%s, %s%s'", $type, $data->options & RangeType::EXCL_START ? '(' : '[', $data->start + 0, $data->end + 0, $data->options & RangeType::EXCL_END ? ')' : ']');
    }
}
