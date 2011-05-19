<?php

namespace Pomm\Converter;

/**
 * ConverterInterface
 *
 * This interface implements 2 methods to convert data from and to postgresql 
 * types
 *
 **/

interface ConverterInterface
{
    public static function fromPg($data);

    public static function toPg($data);
}
