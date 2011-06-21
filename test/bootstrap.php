<?php

namespace Pomm\Test;

use Pomm\Service;
use Pomm\Object\BaseObject;
use Pomm\Object\BaseObjectMap;
use Pomm\Object\Collection;

class TestTableMap extends BaseObjectMap
{
    protected function initialize()
    {
        $this->object_class =  'Pomm\Test\TestTable';
        $this->object_name  =  'book';
        $this->field_definitions  = array(
            'id'               =>    'Integer',
            'created_at'       =>    'Timestamp',
            'last_in'          =>    'Timestamp',
            'last_out'         =>    'Timestamp',
            'title'            =>    'String',
            'authors'          =>    'String[]',
            'is_available'     =>    'Boolean',
            'location'         =>    'Point',
        );
        $this->pk_fields    = array('id');
    }

    public function createTable()
    {
        $sql = "CREATE TABLE book (id SERIAL PRIMARY KEY, created_at TIMESTAMP NOT NULL DEFAULT now(), last_out DATE, last_in DATE, title VARCHAR(256) NOT NULL, authors VARCHAR(255)[] NOT NULL, is_available BOOLEAN NOT NULL DEFAULT true, location POINT)";
        $this->query($sql);
    }

    public function dropTable()
    {
        $sql = "DROP TABLE book";
        $this->query($sql);
    }
}

class TestTable extends BaseObject
{
}
