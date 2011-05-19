<?php
namespace Pomm\Converter;

use Pomm\Converter\ConverterInterface;

class PgLTree implements ConverterInterface
{
    public static function fromPg($data)
    {
        return preg_split('/\./', $data);
    }

    public static function toPg($data)
    {
        return sprintf("'%s'::ltree", join('.', $data));
    }
}
