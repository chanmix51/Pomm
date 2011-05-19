<?php

namespace Pomm\Converter;

use Pomm\Converter\ConverterInterface;

/**
 * Pomm Boolean converter for Postgresql
 **/

class PgBoolean implements ConverterInterface
{
    public function fromPg($data)
    {
        return ($data == 't');
    }

    public function toPg($data)
    {
        return $data ? "'true'" : "'false'";
    }
}
