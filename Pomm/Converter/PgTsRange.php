<?php

namespace Pomm\Converter;

use Pomm\Converter\ConverterInterface;
use Pomm\Exception\Exception;
use Pomm\Type\RangeType;

/**
 * Pomm\Converter\PgTsRange - Timestamp range converter
 *
 * @package Pomm
 * @version $id$
 * @copyright 2011 - 2013 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class PgTsRange implements ConverterInterface
{
    protected $class_name;

    /**
     * __construct()
     *
     * @param String            $class_name      Optional fully qualified TsRange type class name.
     */
    public function __construct($class_name = 'Pomm\Type\TsRange')
    {
        $this->class_name = $class_name;
    }

    /**
     * @see Pomm\Converter\ConverterInterface
     */
    public function fromPg($data, $type = null)
    {
        if (!preg_match('/([\[\(])"([0-9 :-]+)","([0-9 :-]+)"([\]\)])/', $data, $matchs))
        {
            if ($data === null or $data === '')
            {
                return null;
            }

            throw new Exception(sprintf("Bad timestamp range representation '%s' (asked type '%s').", $data, $type));
        }

        $options = $matchs[1] === '(' ? RangeType::EXCL_START : RangeType::INCL_BOUNDS;
        $options += $matchs[4] === ')' ? RangeType::EXCL_END : RangeType::INCL_BOUNDS;

        return new $this->class_name(new \DateTime($matchs[2]), new \DateTime($matchs[3]), $options);
    }

    /**
     * @see Pomm\Converter\ConverterInterface
     */
    public function toPg($data, $type = null)
    {
        if (! $data instanceof \Pomm\Type\TsRange )
        {
            throw new Exception(sprintf("PgTsRange converter expects 'TsRange' data to convert. '%s' given.", gettype($data)));
        }

        return sprintf("%s '%s\"%s\", \"%s\"%s'", $type, $data->options & RangeType::EXCL_START ? '(' : '[', $data->start->format('Y-m-d H:i:s.u'), $data->end->format('Y-m-d H:i:s.u'), $data->options & RangeType::EXCL_END ? ')' : ']');
    }
}
