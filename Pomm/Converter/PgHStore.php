<?php
namespace Pomm\Converter;

use Pomm\Converter\ConverterInterface;
use Pomm\Exception\Exception as PommException;

/**
 * Pomm\Converter\PgHStore - HStore converter
 *
 * @package Pomm
 * @version $id$
 * @copyright 2011 - 2013 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class PgHStore implements ConverterInterface
{
    /**
     * @see \Pomm\Converter\ConverterInterface
     */
    public function fromPg($data, $type = null)
    {
        if ($data === 'NULL' or $data === '') return null;

        @eval(sprintf("\$hstore = array(%s);", $data));

        if (!(isset($hstore) and is_array($hstore)))
        {
            throw new PommException(sprintf("Could not parse hstore string '%s' to array.", $data));
        }

        return $hstore;
    }

    /**
     * @see \Pomm\Converter\ConverterInterface
     */
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
                $insert_values[] = sprintf('"%s" => "%s"', addcslashes($key, '\"'), addcslashes($value, '\"'));
            }
        }

        return sprintf("%s(\$hst\$%s\$hst\$)", $type, join(', ', $insert_values));
    }
}
