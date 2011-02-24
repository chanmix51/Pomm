<?php
namespace Pomm\Type;

class IntType extends BaseType
{
    public function getTypeMatch()
    {
        return 'integer';
    }

    public static function fromPg($data)
    {
        return $data;
    }

    public static function toPg($data)
    {
        return $data;
    }
}
