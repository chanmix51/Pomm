<?php

namespace Pomm\Test\Object;

use Pomm\Connection\Database;
use Pomm\Object\BaseObject;
use Pomm\Object\BaseObjectMap;
use Pomm\Object\SimpleCollection;
use Pomm\Object\Collection;
use Pomm\Exception\Exception;
use Pomm\Query\Where;

class CollectionTest extends ASimpleCollectionTest
{
    public static function setUpBeforeClass()
    {
        $database = parent::setUpBeforeClass();

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
    public function testFilters(SimpleCollection $collection)
    {
        $collection = static::$map->findAll();
        $collection
            ->registerFilter(function ($vals) { return array('id' => $vals['id'] * 2); })
            ->registerFilter(array($this, 'doNothing'))
            ;
        $n = 1;
        foreach ($collection as $entity)
        {
            $this->assertEquals(2 * $n++, $entity->get('id'), "Filter multiply everthing by 2.");
        }
    }

    /**
     * @depends testGetCollection
     **/
    public function testRewind(SimpleCollection $collection)
    {
        $collection = static::$map->findAll();

        foreach ($collection as $result)
        {
        }

        foreach ($collection as $index => $result)
        {
            $this->assertTrue( $index + 1 == $result['id'], "Index follows status.");
        }
    }

    public function testExtract()
    {
        $collection = parent::testExtract();

        $this->assertEquals(array('plop' => array(array('id' => 1), array('id' => 2), array('id' => 3), array('id' => 4))), $collection->extract('plop'), 'Extract is an array of extracts.');
    }

    public function doNothing($values)
    {
        return $values;
    }
}

class CollectionEntityMap extends SimpleCollectionEntityMap
{
    public function createCollectionFromStatement(\PDOStatement $stmt)
    {
        return new \Pomm\Object\Collection($stmt, $this);
    }
}
