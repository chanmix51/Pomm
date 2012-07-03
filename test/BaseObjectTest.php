<?php
namespace Pomm\Test;

use Pomm\Service;
use Pomm\Object\BaseObject;
use Pomm\Object\BaseObjectMap;
use Pomm\Connection\Database;
use Pomm\External\Toolkit;
use Pomm\External\sfInflector;

if (!isset($service)) 
{
    $service = require __DIR__."/init/bootstrap.php";
}

class BaseObjectTest extends \lime_test
{
    protected $map;
    protected $transaction;
    protected $obj;
    protected $service;

    public function initialize(Service $service)
    {
        $this->service = $service;
        $this->transaction = $this->service->getDatabase()->createConnection();
        $this->map = $this->transaction->getMapFor('Pomm\Test\TestTable');

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
        foreach ($tested_values as $field => $value)
        {
            $this->is($value, $this->obj[$field], sprintf('"%s" values match', $field));
        }

        return $this;
    }

    public function testStatus($status)
    {
        $this->is($this->obj->_getStatus(), $status, 'Status is '.$status);

        return $this;
    }

    public function testUnset($field)
    {
        unset($this->obj[$field]);
        $this->ok(!$this->obj->has($field), sprintf("'%s' field does not exist after unset.", $field));

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

    public function testAccessors($field, $expected_value)
    {
        $method_name = sprintf('get%s', sfInflector::camelize($field));

        $this->is($this->obj->$method_name(), $expected_value, 'Accessor works.');
        $this->is($this->obj[$field], $expected_value, 'Array access use accessor.');
        $this->is($this->obj->$field, $expected_value, 'Direct access to attribute use mutator.');

        return $this;
    }

    public function testGenericAccessor($field, $value)
    {
        $this->is($this->obj->get($field), $value, 'Generic getter bypass overloads.');

        return $this;
    }

    public function testMutators($field, $raw_value, $expected_value)
    {
        $method_name = sprintf("set%s", sfInflector::camelize($field));
        $this->obj->$method_name($raw_value);
        $this->is($this->obj->get($field), $expected_value, 'Mutator sets the value.');
        $this->obj[$field] = $raw_value;
        $this->is($this->obj->get($field), $expected_value, 'Array access uses mutator.');
        $this->obj->set($field, $raw_value);
        $this->is($this->obj->get($field), $raw_value, 'Generic mutator bypass overloads.');
        $this->obj->$field = $raw_value;
        $this->is($this->obj->get($field), $expected_value, 'Direct attribute access uses mutator.');

        return $this;
    }

    public function testHas($field, $raw_value, $expected_value)
    {
        if ($raw_value)
        {
            $this->ok($this->obj->has($field), sprintf("Attribute '%s' exists.", $field));
        }
        else
        {
            $this->ok(!$this->obj->has($field), sprintf("Attribute '%s' does NOT exist.", $field));
        }

        if ($expected_value)
        {
            $this->ok(isset($this->obj[$field]), sprintf("Virtual attribute '%s' exists.", $field));
        }
        else
        {
            $this->ok(!isset($this->obj[$field]), sprintf("Virtual attribute '%s' does NOT exist.", $field));
        }

        return $this;
    }
}

$test_values = array('title' => 'modified title', 'authors' => array('plop1', 'plop2'));
$test = new BaseObjectTest();
$test
    ->initialize($service)
    ->create()
    ->testStatus(BaseObject::NONE)
    ->testSet('title', 'my title')
    ->testStatus(BaseObject::MODIFIED)
    ->testUnset('title')
    ->testSet('authors', array('plop1'))
    ->testAdd('authors', 'plop2', array('plop1', 'plop2'))
    ->testHydrate(array('title' => 'modified title'), $test_values)
    ->testHydrate(array('title' => 'modified title'), array_merge($test_values, array('pika' => null)))
    ->testArrayAccess($test_values)
    ->testIteratorAggregate($test_values)
    ->testHydrate(array('title' => 'MODIFIED TITLE'), array())
    ->testAccessors('title', 'modified title')
    ->testGenericAccessor('title', 'MODIFIED TITLE')
    ->testMutators('title', 'uppercase title',  'UPPERCASE TITLE')
    ->testHas('title', true, true)
    ->testHas('title_and_authors', false, true)
    ;


$test->__destruct();
unset($test);
