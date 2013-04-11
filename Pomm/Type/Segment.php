<?php

namespace Pomm\Type;

/**
 * Pomm\Type\Segment - Segment type
 * 
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */

class Segment
{
    public $point_a;
    public $point_b;

    /**
     * __construct
     *
     * @param Point $point_a
     * @param Point $point_b
     */
    public function __construct(Point $point_a, Point $point_b)
    {
        $this->point_a = $point_a;
        $this->point_b = $point_b;
    }
}
