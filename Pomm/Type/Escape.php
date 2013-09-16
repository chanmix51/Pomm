<?php

namespace Pomm\Type;

class Escape
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function __toString()
    {
        return $this->data;
    }
}
