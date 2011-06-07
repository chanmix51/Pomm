<?php
namespace Pomm\Converter;

use Pomm\Converter\ConverterInterface;

/**
 * Pomm\Converter\PgInteger - Integer converter
 * 
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class PgInteger implements ConverterInterface
{
    /**
     * @see ConverterInterface
     **/
    public function fromPg($data)
    {
        return (integer) $data;
    }

    /**
     * @see ConverterInterface
     **/
    public function toPg($data)
    {
        return (integer) $data;
    }
}
