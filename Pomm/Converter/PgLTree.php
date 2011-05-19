<?php
namespace Pomm\Converter;

use Pomm\Converter\ConverterInterface;

class PgLTree implements ConverterInterface
{
    public function fromPg($data)
    {
        return preg_split('/\./', $data);
    }

    public function toPg($data)
    {
        return sprintf("'%s'::ltree", join('.', $data));
    }
}
