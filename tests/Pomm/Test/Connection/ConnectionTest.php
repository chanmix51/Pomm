<?php

namespace Pomm\Test\Connection;

use Pomm\Connection\Database;
use Pomm\Exception\Exception;
use Pomm\Converter;
use Pomm\Type;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    protected static $connection;

    public static function setUpBeforeClass()
    {
        $database = new Database(array('dsn' => $GLOBALS['dsn'], 'name' => 'test_db'));
        static::$connection = $database->createConnection();
    }

    public function testGetMapFor()
    {
        $map1 = static::$connection->getMapFor('\Pomm\Test\Connection\CnxEntity');
        $map2 = static::$connection->getMapFor('\Pomm\Test\Connection\CnxEntity');

        $this->assertTrue($map1 === $map2, "2 calls for the same entity class return the same instance.");

        $map3 = static::$connection->getMapFor('Pomm\Test\Connection\CnxEntity');

        $this->assertTrue($map3 === $map1, "Remove leading backslash returns the same instance.");

        $map4 = static::$connection->getMapFor('Pomm\Test\Connection\CnxEntity', true);

        $this->assertTrue($map4 !== $map1, "Force respawning the instance.");
    }
}

class CnxEntityMap extends \Pomm\Object\BaseObjectMap
{
    protected function initialize()
    {
        $this->object_class =  'Pomm\Test\Connection\CnxEntity';
        $this->object_name  =  'public.generate_series(1, 10) AS id';
        $this->addField('id', 'int4');
        $this->pk_fields    = array('id');
    }
}

class CnxEntity extends \Pomm\Object\BaseObject
{
}
