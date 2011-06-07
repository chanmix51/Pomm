<?php

namespace Pomm\Converter;

/**
 * Pomm\Converter\ConverterInterface - Interface for converters
 * 
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */

interface ConverterInterface
{
    public function fromPg($data);

    public function toPg($data);
}
