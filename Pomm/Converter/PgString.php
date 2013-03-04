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
     * @see Pomm\Converter\ConverterInterface
     **/
    public function toPg($data, $type = null)
    {
        $data = str_replace("'", "''", $data);
        $data = str_replace("\\", "\\\\", $data);
        $type = is_null($type) ? '' : sprintf("%s ", $type);
        $data = sprintf("%s'%s'",  $type, $data);

        return $data;
    }

    /**
     * @see Pomm\Converter\ConverterInterface
     **/
    public function fromPg($data, $type = null)
    {
        return str_replace('\\"', '"', $data);
    }
}
