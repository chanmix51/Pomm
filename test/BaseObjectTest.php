<?php
namespace Pomm;
use Pomm\Object\BaseObject;
use Pomm\Object\BaseObjectMap;

include __DIR__.'/../lib/External/lime.php';
include 
include "autoload.php";

class my_test extends \lime_test
{
    protected $map;
    protected $obj;

    public function initialize()
    {
        Pomm::createConnection('plop', array('dsn' => 'pgsql://user@localhost/nobase'));
        $this->map = Pomm::getMapFor('Pomm\TestTable');
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
}

$my_test = new my_test();
$my_test
    ->initialize()
    ->create()
    ->testStatus(BaseObject::NONE)
    ->testSet('title', 'my title')
    ->testStatus(BaseObject::MODIFIED)
    ->testSet('authors', array('plop1'))
    ->testAdd('authors', 'plop2', array('plop1', 'plop2'))
    ->testHydrate(array('title' => 'modified title'), array('title' => 'modified title', 'authors' => array('plop1', 'plop2')))
    ;

