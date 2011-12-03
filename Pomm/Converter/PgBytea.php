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
     * @see ConverterInterface
     **/
    public function toPg($data)
    {
        return sprintf("E'%s'::bytea", @pg_escape_bytea($data));
    }

    /**
     * @see ConverterInterface
     **/
    public function fromPg($data)
    {
        return @pg_unescape_bytea($data);
    }
}

