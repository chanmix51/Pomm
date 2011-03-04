<?php
namespace Pomm\Type;

abstract class BaseType
{
    public static function toPg($data)
    {
        return $data;
    }

    public static function fromPg($data)
    {
        return $data;
    }
}
