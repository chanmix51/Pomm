<?php
namespace Pomm\Converter;

use Pomm\Converter\ConverterInterface;

/**
 * Pomm\Converter\PgString - String converter 
 * 
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class PgString implements ConverterInterface
{
    /**
     * @see ConverterInterface
     **/
    public function toPg($data)
    {
        $data = str_replace("'", "''", $data);
        $data = sprintf("'%s'", $data);

        return $data;
    }

    /**
     * @see ConverterInterface
     **/
    public function fromPg($data)
    {
        return str_replace('\\"', '"', trim($data, '"'));
    }
}
