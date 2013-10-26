<?php

namespace Pomm\Converter;

use Pomm\Converter\ConverterInterface;
use Pomm\Exception\Exception;

/**
 * Pomm\Converter\PgBoolean - Boolean converter
 *
 * @package Pomm
 * @version $id$
 * @copyright 2011 - 2013 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */

class PgBoolean implements ConverterInterface
{
    /**
     * @see Pomm\Converter\ConverterInterface
     */
    public function fromPg($data, $type = null)
    {
        if (!preg_match('/(t|f)/', $data))
        {
            if ($data === null or $data === '')
            {
                return null;
            }

            throw new Exception(sprintf("Unknown boolean data '%s'.", $data));
        }

        return (bool) ($data === 't');
    }

    /**
     * @see Pomm\Converter\ConverterInterface
     */
    public function toPg($data, $type = null)
    {
        return $data ? "true" : "false";
    }
}
