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
class TsRange extends Range
{
    /**
     * {@inheritDoc}
     */
    public function __construct(\DateTime $start, \DateTime $end, array $options = array())
    {
        parent::__construct($start, $end, $options);
    }
}
