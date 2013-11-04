<?php

namespace Pomm\Type;

/**
 * Pomm\Type\TsRange - Timestamp range type.
 *
 * @package Pomm
 * @version $id$
 * @copyright 2011 - 2013 Grégoire HUBERT
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
        $this->start_included = (bool) $start_included;
        $this->end_included = (bool) $end_included;
    }
}
