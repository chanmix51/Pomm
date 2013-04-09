<?php

namespace Pomm\Type;

/**
 * Pomm\Type\Circle - Circle type
 * 
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class Circle
{

    public $center;
    public $radius;

    /**
     * __construct
     *
     * @param Point   $center
     * @param integer $radius
     */
    public function __construct(Point $center, $radius)
    {
        $this->center = $center;
        $this->radius = $radius;
    }
}
