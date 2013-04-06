<?php

namespace Pomm\Type;

/**
 * Pomm\Type\Point - Point type
 * 
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class Point
{
    public $x;
    public $y;

    /**
     * __construct
     *
     * @param Float $x
     * @param Float $y
     */
    public function __construct($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
    }
}
