<?php

namespace Bench;

use Pomm\Object\BaseObjectMap;
use Pomm\Object\BaseObject;

class PommBenchMap extends BaseObjectMap
{

    protected function initialize()
    {
        $this->object_class = 'Bench\PommBench';
        $this->object_name = 'bench_pomm.bench';
        $this->field_definitions = array(
            'id'          => 'int4',
            'data_int'    => 'int4',
            'data_char'   => 'varchar',
            'data_bool'   => 'bool',
        );
        $this->pk_fields = array('id');
    }

    public function __destruct()
    {
        $this->dropTable();
    }

    public function createTable()
    {
        $sql = sprintf("CREATE SCHEMA %s", reset(preg_split('/\./', $this->object_name)));
        $this->connection->executeAnonymousQuery($sql);
        $sql = sprintf("CREATE TABLE %s (id SERIAL PRIMARY KEY, data_int int4 NOT NULL, data_char VARCHAR NOT NULL, data_bool bool NOT NULL);", $this->object_name);
        $this->connection->executeAnonymousQuery($sql);
    }

    public function dropTable()
    {
        $objects = preg_split('/\./', $this->object_name);
        $sql = sprintf("DROP SCHEMA %s CASCADE;", reset($objects));
        $this->connection->executeAnonymousQuery($sql);
    }

    public function feedTable($rows)
    {
        $sql = sprintf("INSERT INTO %s (data_int, data_char, data_bool) SELECT floor(random() * 10000000), md5(random()::text), (floor(random() * 100)::int4 %% 2) = 0 FROM (SELECT * FROM generate_series(1, ?)) AS x;", $this->object_name);
        $this->query($sql, array($rows));
    }

    public function removePkDefinition()
    {
        $this->pk_fields = array();
    }
}

class PommBench extends BaseObject
{
}

