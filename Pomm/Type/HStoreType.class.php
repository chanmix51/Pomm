<?php
namespace Pomm\Type;

class HStoreType extends BaseType 
{
    public function getTypeMatch()
    {
        return 'hstore';
    }
}
