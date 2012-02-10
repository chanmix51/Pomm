<?php
namespace Pomm\Test;

use Pomm\Service;
use Pomm\Connection\Database;
use Pomm\Object\BaseObject;
use Pomm\Object\BaseObjectMap;
use Pomm\Object\Collection;
use Pomm\Query\Where;
use Pomm\Exception\Exception;

if (!isset($service))
{
    $service = require __DIR__."/init/bootstrap.php";
}

class BaseObjectMapTest extends \lime_test
{
    protected $map;
    protected $obj;
    protected $transac;
    protected $service;

    public function initialize(Service $service)
    {
        $this->service = $service;
        $this->transac = $this->service->getDatabase()->createConnection();
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
        $this->diag('TestTableMap::testObjectFields');
        $obj_values = $this->obj->extract();
        foreach ($values as $name => $value)
        {
            if (is_object($obj_values[$name])) 
            {
                if ($obj_values[$name] instanceof \DateTime)
                {
                    $this->is($obj_values[$name]->format('Y-m-d H:i:s'), $value, sprintf("Comparig datetime '%s'.", $name));
                }
                else continue;
            }
            elseif (is_array($value))
            {
                $this->is_deeply($obj_values[$name], $value, sprintf('Comparing array "%s".', $name));
            }
            else 
            {
                $this->is($value, (string) $obj_values[$name], sprintf('Comparing value "%s"', $name));
            }
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
            $this->is($this->obj->_getStatus(), BaseObject::EXIST, 'Object does exist and is NOT modified');
            $this->ok($this->obj->getId(), 'Object has an ID');
        }
        catch(Exception $e)
        {
            $this->fail(sprintf("Error while saving object.\n===\n%s\n===\n", $e->getMessage()));
            $this->skip(1);
        }

        return $this;
    }

    public function testUpdateOne(Array $fields)
    {
        $this->diag('TestTableMap::updateOne()');

        try
        {
            $this->map->updateOne($this->obj, array_keys($fields));
        }
        catch(Exception $e)
        {
            $this->fail(sprintf("Exception while updating object.\n===\n%s\n===\n", $e->getMessage()));
        }

        $this->is($this->obj->_getStatus(), BaseObject::EXIST, 'Object does exist and is NOT modified');

        foreach($fields as $key => $value)
        {
            $this->is($this->obj[$key], $value, sprintf("New value = '%s'.", $value));
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
            $this->testObjectFields($values);
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
        $this->transac = $this->service->getDatabase()->createConnection()->begin();
        $this->map = $this->transac->getMapFor('Pomm\Test\TestTable');
        $this->obj = $this->map->createObject();

        return $this;
    }

    public function setSavepoint($name)
    {
        $this->info(sprintf('Setting savepoint "%s"', $name));
        $this->transac->setSavepoint($name);

        return $this;
    }

    public function rollback($name = null)
    {
        if (is_null($name))
        {
            $this->info('Rollback whole transaction.');
        }
        else
        {
            $this->info(sprintf('Rollback to savepoint "%s".', $name));
        }
        $this->transac->rollback($name);

        return $this;
    }

    public function commit()
    {
        $this->info('Commit transaction.');
        $this->transac->commit();

        return $this;
    }

    public function testInTransaction($should_we)
    {
        $this->info('Check in transaction or not.');
        if ($should_we)
        {
            $message =  'We are in transaction mode.';
        }
        else
        {
            $message =  'We are NOT in transaction mode';
        }

        $this->is($this->transac->isInTransaction(), $should_we, $message);

        return $this;
    }
}

$test = new BaseObjectMapTest();

$test->initialize($service)
    ->testCreate()
    ->testHydrate(array('title' => 'title test', 'authors' => array('pika chu'), 'is_available' => true), array('title' => 'title test', 'authors' => array('pika chu'), 'is_available' => true))
    ->testSaveOne()
    ->testFindWhere(1)
    ->testQuery('SELECT * FROM pomm_test.book WHERE id < ?', array(10), 1)
    ->testHydrate(array(), array('id' => 1, 'title' => 'title test', 'authors' => array('pika chu'), 'is_available' => true))
    ->testRetreiveByPk(array('id' => 1))
    ->testHydrate(array('title' => 'modified title', 'last_out' => new \DateTime('1975-06-17 21:13'), 'last_in' => new \DateTime('2010-04-01 7:59'), 'authors' => array('pika chu', 'john doe')), array('id' => 1, 'title' => 'modified title', 'last_out' => '1975-06-17 21:13:00', 'last_in' => '2010-04-01 07:59:00', 'authors' => array('pika chu', 'john doe'), 'is_available' => true))
    ->testSaveOne()
    ->testHydrate(array(), array('id' => 1, 'title' => 'modified title', 'authors' => array('pika chu', 'john doe'), 'is_available' => true, 'last_out' => '1975-06-17 21:13:00', 'last_in' => '2010-04-01 07:59:00'))
    ->testRetreiveByPk(array('id' => 1))
    ->testHydrate(array('title' => 'original title'), array('title' => 'original title'))
    ->testUpdateOne(array('title' => 'original title'))
    ->testHydrate(array(), array('title' => 'original title'))
    ->testDeleteOne()
    ->testQuery('SELECT * FROM pomm_test.book', array(), 0)
    ->testFindWhere(0)
    ->testInTransaction(false)
    ->beginTransaction()
    ->testInTransaction(true)
    ->testHydrate(array('id' => 2, 'title' => 'the NO book', 'authors' => array('one', 'two'), 'is_available' => true), array('id' => 2, 'title' => 'the NO book', 'authors' => array('one', 'two'), 'is_available' => true))
    ->testSaveOne()
    ->setSavepoint('a')
    ->testDeleteOne()
    ->testFindWhere(0)
    ->rollback('a')
    ->testInTransaction(true)
    ->testRetreiveByPk(array('id' => 2))
    ->commit()
    ->testInTransaction(false)
    ->testFindWhere(1)
    ;

$test->__destruct();
unset($test);
