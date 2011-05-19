<?php
namespace Pomm\Converter;

use Pomm\Converter\ConverterInterface;

class PgInteger implements ConverterInterface
{
    public static function fromPg($data)
    {
        return (integer) $data;
    }

    public static function toPg($data)
    {
        return (integer) $data;
    }
}
