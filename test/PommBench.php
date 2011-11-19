<?php

namespace Bench;

use Pomm\Object\BaseObjectMap;
use Pomm\Object\BaseObject;

class PommBenchMap extends BaseObjectMap
{

    protected function initialize()
    {
        $this->object_class = 'Bench\PommBench';
        $this->object_name = 'pomm_bench';
        $this->field_definitions = array(
            'id'          => 'Number',
            'data_int'    => 'Number',
            'data_char'   => 'String',
            'data_bool'   => 'Boolean',
        );
        $this->pk_fields = array('id');
    }

    public function createTable()
    {
        $sql = "CREATE TABLE pomm_bench (id SERIAL PRIMARY KEY, data_int INTEGER NOT NULL, data_char VARCHAR NOT NULL, data_bool BOOLEAN NOT NULL);";
        $this->query($sql);
    }

    public function dropTable()
    {
        $sql = "DROP TABLE pomm_bench;";
        $this->query($sql);
    }

    public function feedTable($rows)
    {
        $sql = "INSERT INTO pomm_bench (data_int, data_char, data_bool) SELECT floor(random() * 10000000), md5(random()::text), (floor(random() * 100)::integer % 2) = 0 FROM (SELECT * FROM generate_series(1, ?)) AS x;";
        $this->query($sql, array($rows));
    }
}

class PommBench extends BaseObject
{
}

