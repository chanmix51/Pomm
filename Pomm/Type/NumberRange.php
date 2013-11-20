<?php

namespace Pomm\Type;

/**
 * Pomm\Type\NumberRange - Number range type.
 *
 * @package Pomm
 * @version $id$
 * @copyright 2011 - 2013 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class NumberRange extends RangeType
{
    /**
     * __construct
     *
     * @param mixed $start Range start
     * @param mixed $end   Range end
     * @param int   $option exclude bounds or not
     */
    public function __construct($start, $end, $options = self::INCL_BOUNDS)
    {
        $this->start = $start;
        $this->end = $end;
        $this->options = $options;
    }
}
