<?php
namespace Pomm\Test;

use Pomm\Service;
use Pomm\Object\BaseObject;
use Pomm\Object\BaseObjectMap;
use Pomm\Connection\Database;

include __DIR__.'/../Pomm/External/lime.php';
include "autoload.php";
include "bootstrap.php";

class my_test extends \lime_test
{
    protected $map;
    protected $transaction;
    protected $obj;
    protected $service;

    public function initialize()
    {
        $this->service = new Service();
        $this->service->setDatabase('plop', new Database(array('dsn' => 'pgsql://user@localhost/nobase')));
        $this->transaction = $this->service->getDatabase()->createConnection();
        $this->map = $this->transaction->getMapFor('Pomm\Test\TestTable');
 //       $this->map->createTable();

        return $this;
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
        $this->is($values[$field], $value, 'Value is recorded in the object');

        return $this;
    }

    public function testAdd($field, $value, $test_field)
    {
        $this->obj->add($field, $value);
        $extract = $this->obj->extract();

        $this->is_deeply($extract[$field], $test_field, 'Array is as expected');

        return $this;
    }

    public function testHydrate($values, $tested_values)
    {
        $this->obj->hydrate($values);
        $extract = $this->obj->extract();
        foreach ($tested_values as $field => $value)
        {
            $this->is($value, $extract[$field], sprintf('"%s" values match', $field));
        }

        return $this;
    }

    public function testStatus($status)
    {
        $this->is($this->obj->_getStatus(), $status, 'Status is '.$status);

        return $this;
    }

    public function testArrayAccess($values)
    {
        $this->ok($this->obj instanceof \ArrayAccess, "Implements ArrayAccess.");

        foreach($values as $key => $value)
        {
            $this->is($this->obj[$key], $value, sprintf("Key '%s' is value '%s'.", $key, $value));
        }

        return $this;
    }

    public function testIteratorAggregate()
    {
        $this->ok($this->obj instanceof \IteratorAggregate, "Implements IteratorAggregate.");

        foreach($this->obj as $key => $value)
        {
            $this->is($value, $this->obj[$key], sprintf("Key '%s' is value '%s'.", $key, $value));
        }

        return $this;
    }
}

$test_values = array('title' => 'modified title', 'authors' => array('plop1', 'plop2'));
$my_test = new my_test();
$my_test
    ->initialize()
    ->create()
    ->testStatus(BaseObject::NONE)
    ->testSet('title', 'my title')
    ->testStatus(BaseObject::MODIFIED)
    ->testSet('authors', array('plop1'))
    ->testAdd('authors', 'plop2', array('plop1', 'plop2'))
    ->testHydrate(array('title' => 'modified title'), $test_values)
    ->testArrayAccess($test_values)
    ->testIteratorAggregate($test_values)
    ;

