<?php

namespace Pomm\Test\Object;

use Pomm\Object\BaseObject;
use Pomm\Tools\Inflector;

class BaseObjectTest extends \PHPUnit_Framework_TestCase
{
    public function testHydrate()
    {
        $values = array('pika' => 'chu', 'plop' => null, 'always_true' => true, 'some_in' => 1);
        $entity1 = new Entity($values);
        $entity2 = new Entity();
        $entity2->hydrate(array())->hydrate($values);

        $this->assertTrue($entity1->has('pika'), 'pika attribute exists.');
        $this->assertEquals($entity1->get('pika'), 'chu', 'scalar string is set.');
        $this->assertTrue($entity1->has('plop'), 'Plop attribute exists.');
        $this->assertNull($entity1->get('plop'), 'plop is null.');
        $this->assertTrue($entity1->has('always_true'), 'always_true attribute exists.');
        $this->assertTrue($entity1->get('always_true') === true, 'boolean is set.');
        $this->assertTrue($entity1->has('some_in'), 'some_in attribute exists.');
        $this->assertEquals($entity1->get('some_in'), 1, 'scalar integer is set.');
        $this->assertTrue($entity1 == $entity2, 'Both objects have same attributes.');
    }

    public function testExtract()
    {
        $values = array('pika' => 'chu', 'plop' => null, 'always_true' => true, 'some_in' => 1);
        $expected = array_merge($values, array('plop' => true, 'something' => 'something'));
        $entity = new Entity($values);

        $output = $entity->extract();
        $this->assertTrue(array_key_exists('something', $output), "Custom accessors with 'has' are used.");
        $this->assertTrue(!array_key_exists('nothing', $output), "Custom accessors without 'has' are ignored.");
        $this->assertEquals($expected['something'], $output['something'], "Custom accessors are preserved.");
        $this->assertEquals($expected, $output, "Extract preserves scalars.");

        $entity->set('re_values', $values);
        $expected['re_values'] = $values;
        $this->assertEquals($expected, $entity->extract(), "Extract preserves arrays.");

        $sub_values = array('sub_key' => 'sub_value', 'sub_array' => array(1, 2, 3, 4));
        $sub_expected = array_merge($sub_values, array('plop' => true, 'something' => 'something'));
        $sub_entity = new Entity($sub_values);
        $entity->set('sub_entity', $sub_entity);
        $expected['sub_entity'] = $sub_expected;
        $this->assertEquals($expected, $entity->extract(), "Extract also sub entities.");

        $expected['sub_entities_arr'] = array($sub_expected, $sub_expected, $sub_expected);
        $entity->set('sub_entities_arr', array($sub_entity, $sub_entity, $sub_entity));
        $this->assertEquals($expected, $entity->extract(), "Extract also arrays of sub entities.");
    }

    public function getEntities()
    {
        $data = array('null' => null, 'true' => true, 'first' => 1, 'second' => 2, 'third' => 'plop', 'fourth' => array('one', 'two', 'three'), 'fifth' => '2012-06-18 14:42:07.123456');
        return array(
            array(new Entity(), array()),
            array(new Entity($data), $data)
        );
    }

    /**
     * @dataProvider getEntities
     **/
    public function testMutators(Entity $entity_tpl, Array $values)
    {
        $expected = array_merge($values, array('something' => 'something', 'plop' => true));
        $entity = clone $entity_tpl;
        $entity->set('unknown', 'pikachu');
        $this->assertEquals('pikachu', $entity->get('unknown'), "Unknown key is 'pikachu'.");
        $this->assertEquals(array_merge($expected, array('unknown' => 'pikachu')), $entity->extract(), 'Unknown key is appended.');

        $entity = clone $entity_tpl;
        $entity['unknown'] = 'pikachu';
        $this->assertEquals('pikachu', $entity->get('unknown'), "Array access mutator.");
        $this->assertEquals(array_merge($expected, array('unknown' => 'pikachu')), $entity->extract(), 'Unknown key is appended.');

        $entity = clone $entity_tpl;
        $entity->unknown = 'pikachu';
        $this->assertEquals('pikachu', $entity->get('unknown'), "Direct attribute mutator.");
        $this->assertEquals(array_merge($expected, array('unknown' => 'pikachu')), $entity->extract(), 'Unknown key is appended.');

        $entity = clone $entity_tpl;
        $entity->setUnknown('pikachu');
        $this->assertEquals('pikachu', $entity->get('unknown'), "Setter function.");
        $this->assertEquals(array_merge($expected, array('unknown' => 'pikachu')), $entity->extract(), 'Unknown key is appended.');

        $entity['array'] = array(1);
        $entity->add('array', 2);
        $this->assertEquals(array(1, 2), $entity->get('array'), 'add() fields to array attributes.');

        unset($entity['array']);
        $this->assertFalse($entity->has('array'), "Array does not exist anymore.");

        $entity['array'] = array(1);
        $entity->_setStatus(BaseObject::NONE);
        $entity->clearArray();
        $this->assertFalse($entity->has('array'), "Array does not exist anymore.");
        $this->assertEquals(BaseObject::MODIFIED, $entity->_getStatus(), "Entity is modified when clear is called.");

        $entity['array'] = array(1);
        $entity->clear('array');
        $this->assertFalse($entity->has('array'), "Array does not exist anymore.");
    }

    /**
     * @dataProvider getEntities
     * @expectedException \Pomm\Exception\Exception
     **/
    public function testAccessors(Entity $entity, Array $values)
    {
        foreach ($values as $key => $value) {
            // As it does not work with PHP5.4
            // TODO fix this
            if (is_array($value)) continue;

            $this->assertEquals($value, $entity->get($key), sprintf("'%s' key is '%s'.", $key, $value));
            $this->assertEquals($value, $entity->$key, "Direct attribute access.");
            $this->assertEquals($value, $entity[$key], "Array access.");
            $method = 'get'.ucwords($key);
            $this->assertEquals($value, $entity->{$method}(), "Getter access.");
            $this->assertTrue($entity->has($key), "Key exists");
            $method = 'has'.Inflector::camelize($key);
            $this->assertTrue($entity->{$method}(), "Key exists");
        }

        $this->assertTrue($entity->getPlop(), "'plop' is always true.");
        $this->assertTrue($entity->hasPlop(), "'plop' always exists.");
        $this->assertFalse($entity->has('plop'), "'plop' is not in fields.");
        $this->assertTrue($entity->offsetExists('plop'), "'plop' exists as an array key.");
        $this->assertTrue(!$entity->has('pika'), "'pika' never exists.");

        $entity->getPika();
    }

    /**
     * @dataProvider getEntities
     **/
    public function testStatus(Entity $entity, Array $values)
    {
        $this->assertEquals(BaseObject::NONE, $entity->_getStatus(), 'No state at begining.');
        $this->assertTrue($entity->isNew(), 'No state means IS NEW.');
        $this->assertFalse($entity->isModified(), 'No modification.');
        $entity->_setStatus(BaseObject::EXIST); // fake save
        $this->assertEquals(BaseObject::EXIST, $entity->_getStatus(), 'Status is EXIST.');
        $this->assertFalse($entity->isNew(), 'Not new anymore.');
        $this->assertFalse($entity->isModified(), 'No modification.');
        $entity->setPika('chu');
        $this->assertFalse($entity->isNew(), 'Not new anymore.');
        $this->assertTrue($entity->isModified(), 'Modified.');
        $entity->_setStatus(BaseObject::EXIST); // fake save
        unset($entity['pika']);
        $this->assertFalse($entity->isNew(), 'Not new anymore.');
        $this->assertTrue($entity->isModified(), 'Modified.');
    }
}

class Entity extends BaseObject
{
    public function getPlop()
    {
        return true;
    }

    public function hasPlop()
    {
        return true;
    }

    public function getSomething()
    {
        return 'something';
    }

    public function hasSomething()
    {
        return true;
    }

    public function getNothing()
    {
        return 'nothing';
    }
}
