<?php

include __DIR__.'/../Pomm/External/lime.php';
include "autoload.php";
include "PommBench.php";

use Pomm\Service;
use Pomm\Connection\Database;

class IdentityMapTest extends \lime_test
{
    protected $service;
    protected $map;

    public function initialize()
    {
        $this->service = new Service();
        $this->service->setDatabase('with', new Database(array('dsn' => 'pgsql://greg/greg', 'identity_mapper' => true)));
        $this->service->setDatabase('without', new Database(array('dsn' => 'pgsql://greg/greg', 'identity_mapper' => false)));
        $this->map = $this->service->getDatabase('with')->createConnection(new \Pomm\Identity\IdentityMapper())->getMapFor('Bench\PommBench');
        $this->map->createTable();
        $this->map->feedTable(10);

        return $this;
    }

    public function __destruct()
    {
        $this->map->dropTable();
        parent::__destruct();
    }

    public function testCreateObject()
    {
        $object = $this->map->createObject();
        $object->setDataInt(4);
        $object->setDataChar('plop');
        $object->setDataBool(true);

        $this->map->saveOne($object);
        $object['pika'] = 'chu';

        $other_object = $this->map->findByPk($object->getPrimaryKey());
        $this->ok($other_object->isModified(), 'same instance');
        $this->is($other_object, $object, 'same instance.');
        $this->is($other_object->getPika(), 'chu', 'same instance.');

        return $this;
    }
}



$test = new IdentityMapTest();
$test
    ->initialize()
    ->testCreateObject()
    ;
