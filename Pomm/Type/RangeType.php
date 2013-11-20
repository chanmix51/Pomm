<?php

namespace Pomm\Type;

/**
 * Pomm\Type\Range - Abstact range type
 *
 * @package Pomm
 * @version $id$
 * @copyright 2011 - 2013 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
abstract class RangeType
{
    const INCL_BOUNDS = 0;
    const EXCL_START  = 1;
    const EXCL_END    = 2;
    const EXCL_BOTH   = 3;

    public $start;
    public $end;
    public $options = 0;
}
