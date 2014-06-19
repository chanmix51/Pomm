<?php

namespace Pomm\Test\Object;

use Pomm\Connection\Database;
use Pomm\Object\BaseObject;
use Pomm\Object\BaseObjectMap;
use Pomm\Object\Collection;
use Pomm\Exception\Exception;
use Pomm\Query\Where;

class CollectionTest extends \PHPUnit_Framework_TestCase
{
    protected static $map;
    protected static $logger;

    public static function setUpBeforeClass()
    {
        $database = new Database(array('dsn' => $GLOBALS['dsn'], 'name' => 'test_db'));

        if (isset($GLOBALS['dev']) && $GLOBALS['dev'] == 'true') {
            static::$logger = new \Pomm\Tools\Logger();

            static::$map = $database
                ->createConnection()
                ->registerFilter(new \Pomm\FilterChain\LoggerFilter(static::$logger))
                ->getMapFor('Pomm\Test\Object\CollectionEntity');
        } else {
            static::$map = $database
                ->createConnection()
                ->getMapFor('Pomm\Test\Object\CollectionEntity');
        }

        return $database;
    }

    public static function tearDownAfterClass()
    {
        !is_null(static::$logger) && print_r(static::$logger);
    }

    public function testGetCollection()
    {
        $collection = static::$map->findAll();

        $this->assertInstanceOf('\Pomm\Object\Collection', $collection, "findAll returns Collection instance.");
        $this->assertEquals(10, $collection->count(), "With 10 records");

        return $collection;
    }

    /**
     * @depends testGetCollection
     **/
    public function testResult(Collection $collection)
    {
        $n = 1;
        foreach ($collection as $entity) {
            $this->assertEquals($n++, $entity['id'], sprintf("We have id '%d'.", $n - 1));
        }

        $this->assertEquals($n - 1, $collection->count(), "As many results as count says.");
    }

    /**
     * @depends testGetCollection
     **/
    public function testStats(Collection $collection)
    {
        $this->assertEquals(10, $collection->count(), "Collection has 10 results.");
        $this->assertFalse($collection->isEmpty(), "Collection is NOT empty.");

        $collection = static::$map->findWhere('id > $*', array(135));
        $this->assertEquals(0, $collection->count(), "Collection has no results.");
        $this->assertTrue($collection->isEmpty(), "Collection IS empty.");
    }

    /**
     * @depends testGetCollection
     **/
    public function testRewind(Collection $collection)
    {
        $val1 = $val2 = array();

        foreach ($collection as $entity) {
            $val1[] = $entity;
        }

        $this->assertCount(10, $collection);

        foreach ($collection as $index => $entity) {
            $this->assertTrue($val1[$index] !== $entity, "Iterating twice on a Collection returns the same results");
        }
    }

    /**
     * @depends testGetCollection
     **/
    public function testExtract()
    {
        $collection = static::$map->findWhere('id < $*', array(5));

        $this->assertEquals(array('Pomm\Test\Object\CollectionEntity' => array(array('id' => 1), array('id' => 2), array('id' => 3), array('id' => 4))), $collection->extract(), 'Extract is an array of extracts.');

        return $collection;
    }

    /**
     * @depends testGetCollection
     **/
    public function testFilters(Collection $collection)
    {
        $collection->registerFilter(function($values) { return array_map(function($val) { return $val * 2;
        }, $values);
        });

        foreach ($collection as $index => $entity) {
            $this->assertTrue($entity['id'] == ($index + 1) * 2, "Check filter");
        }
    }
}

class CollectionEntityMap extends BaseObjectMap
{
    protected function initialize()
    {
        $this->object_class = 'Pomm\Test\Object\CollectionEntity';
        $this->object_name  = 'generate_series(1, 10) AS id';

        $this->addField('id', 'int4');

        $this->pk_fields = array('id');
    }
}

class CollectionEntity extends BaseObject
{
}
