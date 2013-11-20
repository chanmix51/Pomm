<?php

namespace Pomm\Type;

/**
 * Pomm\Type\Range - Base range type.
 *
 * @package Pomm
 * @version $id$
 * @copyright 2011 - 2013 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
abstract class Range
{
    const START_INCL = true;
    const START_EXCL = false;
    const END_INCL = true;
    const END_EXCL = false;

    public $start;
    public $end;
    public $start_included;
    public $end_included;

    /**
     * __construct
     *
     * @param mixed $start Range start
     * @param mixed $end   Range end
     * @param array $options Valid options are:
     *  * "start" to specifie if inferior limit should be inclusive or exclusive
     *  * "end" to specifie if superior limit should be inclusive or exclusive
     */
    public function __construct($start, $end, array $options = array())
    {
        $options = $options + array(
            'start' => self::START_INCL,
            'end'   => self::END_INCL,
        );

        $this->start = $start;
        $this->end = $end;
        $this->start_included = (bool) $options['start'];
        $this->end_included = (bool) $options['end'];
    }
}
