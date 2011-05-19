<?php
namespace Pomm\Converter;

use Pomm\Converter\ConverterInterface;

class PgInteger implements ConverterInterface
{
    public function fromPg($data)
    {
        return (integer) $data;
    }

    public function toPg($data)
    {
        return (integer) $data;
    }
}
