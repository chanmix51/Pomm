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
    /**
     * fromPg
     *
     * Parse the output string from Postgresql and returns the converted value 
     * into an according PHP representation.
     *
     * @param $data String  Input string from Pg row result.
     * @param $type String  Optional type.
     * @return Mixed PHP representation of the data.
     */
    public function fromPg($data, $type = null);

    /**
     * toPg
     *
     * Convert a PHP representation into the according Pg formatted string.
     *
     * @param $data Mixed   PHP representation.
     * @param $type String  Optional type.
     * @return String Pg converted string for input.
     */
    public function toPg($data, $type = null);
}
