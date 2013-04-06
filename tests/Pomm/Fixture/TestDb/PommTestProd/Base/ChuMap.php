<?php

namespace TestDb\PommTestProd\Base;

use \Pomm\Object\BaseObjectMap;
use \Pomm\Exception\Exception;

abstract class ChuMap extends \\TestDb\PommTest\PikaMap
{
    public function initialize()
    {
        parent::initialize();

        $this->object_class =  'TestDb\PommTestProd\Chu';
        $this->object_name  =  'pomm_test.chu';

        $this->addField('some_some_type', 'pomm_test.some_type');

        $this->pk_fields = array('');
    }
}