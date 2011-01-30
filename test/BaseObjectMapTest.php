<?php
namespace Pomm\Test;

use Pomm\Pomm;
use Pomm\Object\BaseObject;
use Pomm\Object\BaseObjectMap;
use Pomm\Object\Collection;
use Pomm\Query\Where;
use Pomm\Exception\Exception;

include __DIR__.'/../lib/External/lime.php';
include "autoload.php";
include "bootstrap.php";

class my_test extends \lime_test
{
    protected $map;
    protected $obj;
    protected $transac;

    public function initialize()
    {
        Pomm::setDatabase('default', array('dsn' => 'pgsql://greg/greg'));
        $this->transac = Pomm::getDatabase()->createConnection();
        $this->transac->getMapFor('Pomm\Test\TestTable')->createTable();

        return $this;
    }

    public function __destruct()
    {
        $this->map->dropTable();
    }

    public function resetObjects()
    {
        $this->map = null;
        $this->obj = null;
        $this->transac = null;

        return $this;
    }

    public function testCreate()
    {
        $this->diag('TestTableMap::createObject()');
        $this->map = $this->transac->getMapFor('Pomm\Test\TestTable');
        $this->obj = $this->map->createObject();

        $this->isa_ok($this->obj, 'Pomm\\Test\\TestTable', 'TestTableMap::createObject() returns a Pomm\\Test\\TestTable instance');
        $this->is($this->obj->_getStatus(), BaseObject::NONE, 'Object does not exist nor is modified');

        return $this;
    }

    protected function testObjectFields($values)
    {
        foreach ($this->obj->extract() as $name => $value)
        {
            if (gettype($value) == 'object') continue;
            $this->is($value, $values[$name], sprintf('Comparing "%s"', $name));
        }

        return $this;
    }

    public function testHydrate($values, $tested_values)
    {
        $this->diag('TestTableMap::hydrate()');
        $this->obj->hydrate($values);
        $this->testObjectFields($tested_values);

        return $this;
    }

    public function testSaveOne()
    {
        $this->diag('TestTableMap::saveOne()');
        try
        {
            $this->map->saveOne($this->obj);
            $this->is($this->obj->_getStatus(), BaseObject::EXIST, 'Object does exist but is NOT modified');
            $this->ok($this->obj->getId(), 'Object has an ID');
        }
        catch(Exception $e)
        {
            $this->fail(sprintf("Error while saving object.\n===\n%s\n===\n", $e->getMessage()));
            $this->skip(1);
        }

        return $this;
    }

    public function testRetreiveByPk($values)
    {
        $this->diag('TestTableMap::findByPk()');
        $object = $this->map->findByPk($values);
        if (!$object)
        {
            $this->fail('No record found');
        }
        else
        {
            $this->testObjectFields($object->extract());
        }

        return $this;
    }

    public function testDeleteOne()
    {
        $this->diag('TestTableMap::deleteOne()');
        try
        {
            $this->map->deleteOne($this->obj);
            $this->pass('No error during deletion');
            $this->is($this->obj->_getStatus(), BaseObject::NONE, 'status = NONE');
            $this->ok(is_null($this->map->findByPk(array('id' => $this->obj->getId()))), 'Record is not in the database anymore');
        }
        catch (Exception $e)
        {
            $this->fail('Deletion error');
        }

        return $this;
    }

    public function testQuery($sql, $values, $num_result)
    {
        $this->diag('TestTableMap::query()');
        $results = $this->map->query($sql, $values);
        $this->isa_ok($results, 'Pomm\\Object\\Collection', 'The result is a collection');
        $this->is($results->count(), $num_result, 'We have the good number of results');

        return $this;
    }

    public function testFindWhere($count)
    {
        $this->diag('TestTableMap::Where()');
        $this->is($this->map->findWhere(new Where())->count(), $count, 'Pomm\\Query\\Where returns expected results count');

        return $this;
    }

    public function beginTransaction()
    {
        $this->info('Starting transaction');
        $this->transac->begin();

        return $this;
    }

    public function setSavepoint($name)
    {
        $this->info(sprintf('Seting savepoint "%s"', $name));
        $this->transac->setSavepoint($name);

        return $this;
    }

    public function rollback($name = null)
    {
        $this->transac->rollback($name);
        if (is_null($name))
        {
            $this->info('Rollback whole transaction.');
        }
        else
        {
            $this->info(sprintf('Rollback to savepoint "%s".', $name));
        }

        return $this;
    }

    public function commit()
    {
        $this->transac->commit();
        $this->info('Commit transaction.');

        return $this;
    }
}

$test = new my_test();
$test->initialize()
    ->testCreate()
    ->testHydrate(array('title' => 'title test', 'authors' => array('pika chu'), 'is_available' => true), array('title' => 'title test', 'authors' => array('pika chu'), 'is_available' => true))
    ->testSaveOne()
    ->testFindWhere(1)
    ->testQuery('SELECT * FROM book WHERE id < ?', array(10), 1)
    ->testHydrate(array(), array('id' => 1, 'title' => 'title test', 'authors' => array('pika chu'), 'is_available' => true))
    ->testRetreiveByPk(array('id' => 1))
    ->testHydrate(array('title' => 'modified title', 'authors' => array('pika chu', 'john doe')), array('id' => 1, 'title' => 'modified title', 'authors' => array('pika chu', 'john doe'), 'is_available' => true))
    ->testSaveOne()
    ->testHydrate(array(), array('id' => 1, 'title' => 'modified title', 'authors' => array('pika chu', 'john doe'), 'is_available' => true))
    ->testRetreiveByPk(array('id' => 1))
    ->testDeleteOne()
    ->testQuery('SELECT * FROM book', array(), 0)
    ->testFindWhere(0)
    ->beginTransaction()
    ->testHydrate(array('id' => 2, 'title' => 'the NO book', 'authors' => array('one', 'two'), 'is_available' => true), array('id' => 2, 'title' => 'the NO book', 'authors' => array('one', 'two'), 'is_available' => true))
    ->testSaveOne()
    ->setSavepoint('a')
    ->testDeleteOne()
    ->testFindWhere(0)
    ->rollback('a')
    ->testRetreiveByPk(array('id' => 2))
    ->commit()
    ->testFindWhere(1)
    ;
