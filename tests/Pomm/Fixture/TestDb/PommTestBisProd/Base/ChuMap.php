<?php

namespace TestDb\PommTestBisProd\Base;

use \Pomm\Object\BaseObjectMap;
use \Pomm\Exception\Exception;

/**
 * ChuMap
 *
 * Structure definition for class ChuMap.
 *
 * This is a useful comment on table chu.
 *
 */
abstract class ChuMap extends \TestDb\PommTest\PikaMap
{
    public function initialize()
    {
        parent::initialize();

        $this->object_class =  'TestDb\PommTestBisProd\Chu';
        $this->object_name  =  '"pomm_test_bis"."chu"';

        $this->addField('some_some_type', 'pomm_test_bis.some_type'); // comment on some_some_type

        $this->pk_fields = array('');
    }
}
