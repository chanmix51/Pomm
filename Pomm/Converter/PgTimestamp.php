<?php
namespace Pomm\Converter;

use Pomm\Converter\ConverterInterface;

/**
 * Pomm\Converter\PgTimestamp - Date and timestamp converter
 *
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class PgTimestamp implements ConverterInterface
{
    /**
     * @see Pomm\Converter\ConverterInterface
     **/
    public function fromPg($data, $type = null)
    {
        return new \DateTime($data);
    }

    /**
     * @see Pomm\Converter\ConverterInterface
     **/
    public function toPg($data, $type = null)
    {
        if (!$data instanceof \DateTime) {
            $data = new \DateTime($data);
        }

        return sprintf("%s '%s'", $type, $data->format('Y-m-d H:i:s.u'));
    }
}
