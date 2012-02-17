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
     * @see ConverterInterface
     **/
    public function fromPg($data)
    {
        return new \DateTime($data);
    }

    /**
     * @see ConverterInterface
     **/
    public function toPg($data)
    {
        if (!$data instanceof \DateTime)
        {
            $data = new \DateTime($data);
        }

        return sprintf("'%s'::timestamp", $data->format('Y-m-d H:i:s.u'));
    }
}
