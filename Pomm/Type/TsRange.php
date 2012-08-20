<?php

namespace Pomm\Type;

/**
 * Pomm\Type\TsRange - Timestamp range type.
 * 
 * @package Pomm
 * @version $id$
 * @copyright 2012 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */

class TsRange
{
    public $start;
    public $end;
    public $start_included;
    public $end_included;

    public function __construct(\DateTime $start, \DateTime $end, $start_included = false, $end_included = false)
    {
        $this->start = $start;
        $this->end = $end;
        $this->start_included = $start_included;
        $this->end_included = $end_included;
    }

    public function __toString()
    {
        return sprintf("%s%s, %s%s", $this->start_included ? '[' : '(', $this->start->format('Y-m-d H:i:s.u'), $this->end->format('Y-m-d H:i:s.u'), $this->end_included ? ']' : ')');
    }
}
