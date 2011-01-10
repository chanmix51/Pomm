<?php

include(dirname(__FILE__).'/../bootstrap/unit.php');

class my_test 
{ 
  protected $test;
  protected $map;
  protected $obj;

  public function __construct()
  {
    $this->test = new lime_test();
    PgLook::getMapFor('TestTable')->createTable();
  }

  public function resetObjects()
  {
    $this->map = null;
    $this->obj = null;

    return $this;
  }

  public function testCreate()
  {
    $this->test->diag('TestTableMap::createObject()');
    $this->map = PgLook::getMapFor('TestTable');
    $this->obj = $this->map->createObject();

    $this->test->isa_ok($this->obj, 'TestTable', 'TestTableMap::createObject() returns a TestTable instance');
    $this->test->is($this->obj->_getStatus(), PgLookBaseObject::NONE, 'Object does not exist nor is modified');

    return $this;
  }

  protected function testObjectFields($values)
  {
    foreach ($this->obj->extract() as $name => $value)
    {
      if (gettype($value) == 'object') continue;
      $this->test->is($value, $values[$name], sprintf('Comparing "%s"', $name));
    }

    return $this;
  }

  public function testHydrate($values, $tested_values)
  {
    $this->test->diag('TestTableMap::hydrate()');
    $this->obj->hydrate($values);
    $this->testObjectFields($tested_values);

    return $this;
  }

  public function testSaveOne()
  {
    $this->test->diag('TestTableMap::saveOne()');
    try
    {
      $this->map->saveOne($this->obj);
      $this->test->is($this->obj->_getStatus(), PgLookBaseObject::EXIST, 'Object does exist but is NOT modified');
      $this->test->ok($this->obj->getId(), 'Object has an ID');
    }
    catch(PgLookException $e)
    {
      $this->test->fail('Error while saving object');
      $this->test->skip(1);
    }

    return $this;
  }

  public function testRetreiveByPk($values)
  {
    $this->test->diag('TestTableMap::findByPk()');
    $object = $this->map->findByPk($values);
    $this->testObjectFields($object->extract());

    return $this;
  }

  public function testDeleteOne()
  {
    $this->test->diag('TestTableMap::deleteOne()');
    try
    {
      $this->map->deleteOne($this->obj);
      $this->test->pass('No error during deletion');
      $this->test->is($this->obj->_getStatus(), PgLookBaseObject::NONE, 'status = NONE');
      $this->test->ok(is_null($this->map->findByPk(array('id' => $this->obj->getId()))), 'Record is not in the database anymore');
    }
    catch (PgLookException $e)
    {
      $this->test->fail('Deletion error');
    }

    return $this;
  }

  public function testQuery($sql, $values, $num_result)
  {
    $this->test->diag('TestTableMap::query()');
    $results = $this->map->query($sql, $values);
    $this->test->isa_ok($results, 'PgLookCollection', 'The result is a collection');
    $this->test->is($results->count(), $num_result, 'We have the good number of results');

    return $this;
  }

  public function testFindPgLookWhere($count)
  {
    $this->test->diag('TestTableMap::findPgLookWhere()');
    $this->test->is($this->map->findPgLookWhere(new PgLookWhere())->count(), $count, 'PgLookWhere returns expected results count');

    return $this;
  }
}

$test = new my_test();
$test->testCreate()
  ->testHydrate(array('title' => 'title test', 'authors' => array('pika chu')), array('title' => 'title test', 'authors' => array('pika chu')))
  ->testSaveOne()
  ->testFindPgLookWhere(1)
  ->testQuery('SELECT * FROM test_table WHERE id < ?', array(10), 1)
  ->testHydrate(array(), array('id' => 1, 'title' => 'title test', 'authors' => array('pika chu'), 'is_ok' => true))
  ->testRetreiveByPk(array('id' => 1))
  ->testHydrate(array('title' => 'modified title', 'authors' => array('pika chu', 'john doe')), array('id' => 1, 'title' => 'modified title', 'authors' => array('pika chu', 'john doe'), 'is_ok' => true))
  ->testSaveOne()
  ->testHydrate(array(), array('id' => 1, 'title' => 'modified title', 'authors' => array('pika chu', 'john doe'), 'is_ok' => true))
  ->testRetreiveByPk(array('id' => 1))
  ->testDeleteOne()
  ->testQuery('SELECT * FROM test_table', array(), 0)
  ->testFindPgLookWhere(0)
  ;
