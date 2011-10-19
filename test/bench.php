<?php

namespace Bench;

use Pomm\Object\BaseObjectMap;
use Pomm\Object\BaseObject;

include __DIR__.'/autoload.php';

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

$db = new \Pomm\Connection\Database(array('dsn' => 'pgsql://greg/greg'));

$map = $db->createConnection()
    ->getMapFor('Bench\PommBench')
   ;

switch(strtolower($argv[1])) 
{
case "create":
    $map->createTable();
    printf("Create Table.\n");
case "populate":
    printf("Populate Table with '%d' rows. (This may take a while).\n", $argv[2]);
    $map->feedTable($argv[2]);
    break;
case "drop":
    printf("Drop Table.");
    $map->dropTable();
    break;
case "bench":
    printf("Bench !\n");

    $collection = $map->findAll();
    printf("Count = '%d'.\n", $collection->count());
    foreach ($collection as $result)
    {
        sprintf(" %d | %d | %s | %s\n", $result['id'], $result['data_int'], $result['data_char'], $result['data_bool'] ? 'true' : 'false');
    }
}
