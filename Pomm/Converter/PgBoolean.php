<?php

namespace Pomm\Converter;

use Pomm\Converter\ConverterInterface;

/**
 * Pomm\Converter\PgBoolean - Boolean converter
 * 
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */

class PgBoolean implements ConverterInterface
{
    /**
     * @see Pomm\Converter\ConverterInterface
     **/
    public function fromPg($data, $type = null)
    {
        return ($data == 't');
    }

    /**
     * @see Pomm\Converter\ConverterInterface
     **/
    public function toPg($data, $type = null)
    {
        return $data ? "true" : "false";
    }
}
