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
    $this->map = PgLook::getMapFor('TestTable');
    $this->map->createTable();
  }

  public function create()
  {
    $this->obj = $this->map->createObject();

    return $this;
  }

  public function testSet($field, $value)
  {
    $this->obj->set($field, $value);
    $values = $this->obj->extract();
    $this->test->is($values[$field], $value, 'Value is recorded in the object');

    return $this;
  }

  public function testAdd($field, $value, $test_field)
  {
    $this->obj->add($field, $value);
    $extract = $this->obj->extract();

    $this->test->is_deeply($extract[$field], $test_field, 'Array is as expected');

    return $this;
  }

  public function testHydrate($values, $tested_values)
  {
    $this->obj->hydrate($values);
    $extract = $this->obj->extract();
    foreach ($tested_values as $field => $value)
    {
      $this->test->is($value, $extract[$field], sprintf('"%s" values match', $field));
    }

    return $this;
  }

  public function testStatus($status)
  {
    $this->test->is($this->obj->_getStatus(), $status, 'Status is '.$status);

    return $this;
  }
}

$my_test = new my_test();
$my_test->create()
  ->testStatus(PgLookBaseObject::NONE)
  ->testSet('title', 'my title')
  ->testStatus(PgLookBaseObject::MODIFIED)
  ->testSet('authors', array('plop1'))
  ->testAdd('authors', 'plop2', array('plop1', 'plop2'))
  ->testHydrate(array('title' => 'modified title'), array('title' => 'modified title', 'authors' => array('plop1', 'plop2')))
  ;

