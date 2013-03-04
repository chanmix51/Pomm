<?php

namespace Pomm\Type;

/**
 * Pomm\Type\NumberRange - Number range type.
 *
 * @package Pomm
 * @version $id$
 * @copyright 2012 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */

class NumberRange
{
    public $start;
    public $end;
    public $start_included;
    public $end_included;

    public function __construct($start, $end, $start_included = false, $end_included = false)
    {
        $this->start = $start;
        $this->end = $end;
        $this->start_included = (bool) $start_included;
        $this->end_included = (bool) $end_included;
    }
}
