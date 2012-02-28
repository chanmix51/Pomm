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
    public function toPg($data, $type = null)
    {
        $data = str_replace("'", "''", $data);
        $type = is_null($type) ? '' : sprintf("%s ", $type);
        $data = sprintf("%s'%s'", $data, $type);

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
