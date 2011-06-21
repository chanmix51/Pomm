<?php
namespace Pomm\Converter;

use Pomm\Converter\ConverterInterface;

/**
 * Pomm\Converter\PgNumber - Number converter
 * 
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class PgNumber implements ConverterInterface
{
    /**
     * @see ConverterInterface
     **/
    public function fromPg($data)
    {
        return $data;
    }

    /**
     * @see ConverterInterface
     **/
    public function toPg($data)
    {
        return $data;
    }
}
