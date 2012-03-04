<?php
namespace Pomm\Converter;

use Pomm\Converter\ConverterInterface;
use Pomm\Exception\Exception as PommException;

/**
 * Pomm\Converter\PgHStore - HStore converter
 * 
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class PgHStore implements ConverterInterface
{
    /**
     * @see Pomm\Converter\ConverterInterface
     **/
    public function fromPg($data, $type = null)
    {
        $split = preg_split('/[,\s]*"([^"]+)"[,\s]*|[,=>\s]+/', $data, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $hstore = array();

        for ($index = 0; $index < count($split); $index = $index + 2)
        {
            $hstore[$split[$index]] = $split[$index + 1] != 'NULL' ? $split[$index + 1] : null;
        }

        return $hstore;
    }

    /**
     * @see Pomm\Converter\ConverterInterface
     **/
    public function toPg($data, $type = null)
    {
        if (!is_array($data))
        {
            throw new PommException(sprintf("HStore::toPg takes an associative array as parameter ('%s' given).", gettype($data)));
        }

        $insert_values = array();

        foreach($data as $key => $value)
        {
            if (is_null($value))
            {
                $insert_values[] = sprintf('"%s" => NULL', $key);
            }
            else
            {
                $insert_values[] = sprintf('"%s" => "%s"', $key, $value);
            }
        }

        return sprintf("'%s'::hstore", join(', ', $insert_values));
    }
}
