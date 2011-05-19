<?php

namespace Pomm\Converter;

use Pomm\Converter\ConverterInterface;

/**
 * Pomm Boolean converter for Postgresql
 **/

class PgBoolean implements ConverterInterface
{
    public static function fromPg($data)
    {
        return ($data == 't');
    }

    public static function toPg($data)
    {
        return $data ? "'true'" : "'false'";
    }
}
