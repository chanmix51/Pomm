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
class TsRange extends RangeType
{
    public $start;
    public $end;
    public $start_included;
    public $end_included;

    public function __construct(\DateTime $start, \DateTime $end, $options = self::INCL_BOUNDS)
    {
        $this->start = $start;
        $this->end = $end;
        $this->options = $options;
    }
}
