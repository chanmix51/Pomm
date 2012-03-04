<?php
namespace Pomm\Converter;

use Pomm\Converter\ConverterInterface;

/**
 * Pomm\Converter\PgBytea - Bytea converter
 * 
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class PgBytea implements ConverterInterface
{
    /**
     * escByteA
     *
     * Does the job of pg_escape_bytea in PHP.
     * @link http://php.net/manual/fr/function.pg-escape-bytea.php
     *
     * @param String $data Binary string to be escaped.
     * @return String
     **/
    protected function escByteA($data)
    {
        $search = array(chr(92), chr(0), chr(39)); 
        $replace = array('\\\134', '\\\000', '\\\047'); 
        $data = str_replace($search, $replace, $data); 

        return $data;
    }

    /**
     * @see Pomm\Converter\ConverterInterface
     **/
    public function toPg($data, $type = null)
    {
        if (function_exists('pg_escape_bytea'))
        {
            return sprintf("bytea '%s'", pg_escape_bytea($data));
        }
        else
        {
            return $this->escByteA($data);
        }
    }

    /**
     * @see Pomm\Converter\ConverterInterface
     **/
    public function fromPg($data, $type = null)
    {
        return stripcslashes(@stream_get_contents($data));
    }
}

