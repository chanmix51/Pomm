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
    public function fromPg($data);

    public function toPg($data);
}
