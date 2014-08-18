<?php

namespace Pomm\Test\Object;

use Pomm\Connection\Database;
use Pomm\Object\BaseObject;
use Pomm\Object\BaseObjectMap;
use Pomm\Object\Collection;
use Pomm\Exception\Exception as PommException;
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
        foreach($collection as $entity) 
        {
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

        foreach($collection as $entity)
        {
            $val1[] = $entity;
        }

        $this->assertCount(10, $collection);

        foreach($collection as $index => $entity)
        {
            $this->assertTrue($val1[$index] !== $entity, "Iterating twice on a Collection returns the same results");
        }
    }

    /**
     * @depends testGetCollection
     **/
    public function testExtract()
    {
        $collection = static::$map->findWhere('id < $*', array(5));

        $this->assertEquals(array('Pomm\Test\Object\CollectionEntity' => array(array('id' => 1, 'data' => 9), array('id' => 2, 'data' => 8), array('id' => 3, 'data' => 7), array('id' => 4, 'data' => 6))), $collection->extract(), 'Extract is an array of extracts.');

        return $collection;
    }

    /**
     * @depends testGetCollection
     **/
    public function testFilters(Collection $collection)
    {
        $collection->registerFilter(function($values) { return array_map(function($val) { return $val * 2; }, $values); });

        foreach ($collection as $index => $entity)
        {
            $this->assertTrue($entity['id'] == ($index + 1) * 2, "Check filter");
        }
    }

    /**
     * @depends testGetCollection
     */
    public function testSlice(Collection $collection)
    {
        $data1 = $collection->slice('id');

        $this->assertEquals(10, count($data1), 'data1 has 10 rows.');
        $this->assertEquals(1, $data1[0], 'First id is 1.');
        $this->assertEquals(10, $data1[9], 'Last id is 10.');

        $data2 = $collection->slice('id');

        $this->assertEquals($data1, $data2, 'Slice is idempotent.');

        $data3 = $collection->slice('data');
        $this->assertEquals(10, count($data3), 'data3 has 10 rows.');
        $this->assertEquals(0, $data3[9], 'Last id is 0.');

        try
        {
            $collection->slice('pika');
            $this->fail('slice on non existent field should throw a PommException.');
        }
        catch(PommException $e)
        {
            $this->assertTrue(true, 'slice on non existent field should throw a PommException.');
        }
        catch(Exception $e)
        {
            $this->fail(sprintf("slice on non existent field should throw a PommException ('%s' thrown).", get_class($e)));
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

    public function getSelectFields($alias = null)
    {
        $fields = parent::getSelectFields($alias);
        $fields['data'] = sprintf("10 - %s", $this->aliasField('id', $alias));

        return $fields;
    }
}

class CollectionEntity extends BaseObject
{
}
