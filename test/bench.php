<?php

include __DIR__.'/init/bootstrap.php';
include __DIR__.'/PommBench.php';


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
