<?php

namespace TestDb\PommTest\Base;

use \Pomm\Object\BaseObjectMap;
use \Pomm\Exception\Exception;

/**
 * PikaMap
 *
 * Structure definition for class PikaMap.
 */
abstract class PikaMap extends BaseObjectMap
{
    public function initialize()
    {

        $this->object_class =  'TestDb\PommTest\Pika';
        $this->object_name  =  '"pomm_test"."pika"';

        $this->addField('id', 'int4');
        $this->addField('some_char', 'bpchar');
        $this->addField('some_varchar', 'varchar');
        $this->addField('fixed_arr', 'numeric[]');

        $this->pk_fields = array('id');
    }
}
