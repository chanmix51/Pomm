<?php

require __DIR__.'/../Pomm/External/lime.php';
require "autoload.php";
require "PommBench.php";
$service = require "bootstrap.php";

use Pomm\Service;
use Pomm\Connection\Database;
use Pomm\Identity\IdentityMapperNone;
use Pomm\Identity\IdentityMapperStrict;
use Pomm\Identity\IdentityMapperSmart;
use Pomm\Identity\IdentityMapperInterface;

class IdentityMapTest extends \lime_test
{
    protected $service;
    protected $map;

    public function initialize($service)
    {
        $this->service = $service;
        $this->map = $this->service->getDatabase()->createConnection(new Pomm\Identity\IdentityMapperNone())->getMapFor('Bench\PommBench');
        $this->map->createTable();
        $this->map->feedTable(10);

        return $this;
    }

    public function setMapper(IdentityMapperInterface $mapper)
    {
        $this->map = $this->service->getDatabase()->createConnection($mapper)->getMapFor('Bench\PommBench');

        return $this;
    }

    public function __destruct()
    {
        $this->map->dropTable();
        parent::__destruct();
    }

    public function testCreateObject($same = true)
    {
        $object = $this->map->createObject();
        $object->setDataInt(4);
        $object->setDataChar('plop');
        $object->setDataBool(true);

        $this->map->saveOne($object);
        $object['pika'] = 'chu';
        $other_object = $this->map->findByPk($object->get($this->map->getPrimaryKey()));
        if ($same === true)
        {
            $this->ok($other_object->isModified(), 'same instance');
            $this->is($other_object, $object, 'same instance.');
            $this->is($other_object->getPika(), 'chu', 'same instance.');
        }
        else
        {
            $this->is_deeply($other_object->get($this->map->getPrimaryKey()), $object->get($this->map->getPrimaryKey()), "They have the same PK.");
            $this->is(!$other_object->isModified(), "New object is not modified.");
        }

        return $this;
    }

    public function testDelete($returned = false)
    {
        $collection = $this->map->query(sprintf("SELECT %s FROM %s LIMIT 1", join(', ', $this->map->getSelectFields()), $this->map->getTableName()));
        $object = $collection->current();
        $this->map->deleteOne($object);

        $other_object = $this->map->findByPk($object->get($this->map->getPrimaryKey()));

        if ($returned === true) 
        {
            $this->ok($other_object instanceof \Pomm\Object\BaseObject, "Deleted object is returned by IM.");
        }
        else
        {
            $this->ok(is_null($other_object), "Deleted objects are not returned by IM.");
        }

        return $this;
    }
}


$test = new IdentityMapTest();
$test
    ->initialize()
    ->testCreateObject(false)
    ->setMapper(new IdentityMapperStrict())
    ->testCreateObject()
    ->setMapper(new IdentityMapperSmart())
    ->testCreateObject()
    ->setMapper(new IdentityMapperNone())
    ->testDelete()
    ->setMapper(new IdentityMapperStrict())
    ->testDelete(true)
    ->setMapper(new IdentityMapperSmart())
    ->testDelete()
    ;
