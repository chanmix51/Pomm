<?php

namespace Pomm\Test\Connection;

use Pomm\Connection\Database;
use Pomm\Exception\Exception as PommException;
use Pomm\Exception\SqlException;
use Pomm\Exception\ConnectionException;
use Pomm\Converter;
use Pomm\Type;
use Pomm\Query\PreparedQuery;
use Pomm\Connection\Connection;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    protected static $connection;

    public static function setUpBeforeClass()
    {
        $database = new Database(array('dsn' => $GLOBALS['dsn'], 'name' => 'test_db'));
        static::$connection = $database->createConnection();
    }

    public function tearDown()
    {
        try
        {
            static::$connection->executeAnonymousQuery("DROP SCHEMA pomm_test CASCADE");
        }
        catch(ConnectionException $e)
        {
        }
    }

    /**
     * @expectedException \Pomm\Exception\ConnectionException
     * @expectedExceptionMessage Error connecting to the database with dsn 'user=invalid_user dbname=pomm_test host=127.0.0.1 password=invalid_password'.
     */
    public function testInvalidConnection()
    {
        $database = new Database(array('dsn' => 'pgsql://invalid_user:invalid_password@127.0.0.1/pomm_test', 'name' => 'invalid_db'));
        $connection = $database->createConnection();
        $connection->getHandler();
    }

    public function testMultipleConnection()
    {
        $database = new Database(array('dsn' => $GLOBALS['dsn'], 'name' => 'test_db'));
        $connection = $database->createConnection();
    }

    public function testGetMapFor()
    {
        $map1 = static::$connection->getMapFor('\Pomm\Test\Connection\CnxEntity');
        $map2 = static::$connection->getMapFor('\Pomm\Test\Connection\CnxEntity');
        $this->assertTrue($map1 instanceOf \Pomm\Test\Connection\CnxEntityMap, 'This is a CnxEntityMap.');
        $this->assertTrue($map1 === $map2, "2 calls for the same entity class return the same instance.");

        $map3 = static::$connection->getMapFor('Pomm\Test\Connection\CnxEntity');
        $this->assertTrue($map3 === $map1, "Remove leading backslash returns the same instance.");

        $map4 = static::$connection->getMapFor('Pomm\Test\Connection\CnxEntity', true);
        $this->assertTrue($map4 !== $map1, "Force respawning the instance.");

        $map5 = static::$connection->getMapFor('Pomm\Test\Connection\CnxOtherEntity', true);
        $this->assertTrue($map5 instanceOf \Pomm\Test\Connection\CnxOtherEntityMap, 'This is a CnxOtherEntityMap.');
        $this->assertTrue($map5 !== $map4, "Asking differents classes return different classes.");
    }

    public function testFilterChain()
    {
        $sql = "SELECT $*::int";

        $stmt = static::$connection->query($sql, array(1));
        $this->assertEquals(1, pg_num_rows($stmt), "We have one result returned by Postgresql");
    }

    public function testExecuteAnonymousQuery()
    {
        try
        {
            static::$connection->executeAnonymousQuery('pikaaaaaa');
            $this->assertTrue(false, 'Failed anonymous queries should throw a ConnectionException (no exception caught).');
        }
        catch (\Pomm\Exception\ConnectionException $e)
        {
            $this->assertTrue(true, 'Failed anonymous queries should throw a ConnectionException.');
        }
        catch (\Exception $e)
        {
            $this->assertTrue(false, sprintf("Failed anonymous queries should throw a ConnectionException (%s caught).", get_class($e)));
        }

        try
        {
            static::$connection->executeAnonymousQuery('select true');
            $this->assertTrue(true, 'No exception thrown on valid sql statements.');
        }
        catch (\Exception $e)
        {
            $this->assertTrue(false, sprintf("No exception thrown on valid sql statements. ('%s' caught).", get_class($e)));
        }
    }

}

class CnxEntityMap extends \Pomm\Object\BaseObjectMap
{
    protected function initialize()
    {
        $this->object_class =  'Pomm\Test\Connection\CnxEntity';
        $this->object_name  =  'generate_series(1, 10) AS id';
        $this->addField('id', 'int4');
        $this->pk_fields    = array('id');
    }
}

class CnxOtherEntityMap extends \Pomm\Object\BaseObjectMap
{
    protected function initialize()
    {
        $this->object_class =  'Pomm\Test\Connection\CnxOtherEntity';
        $this->object_name  =  'generate_series(1, 10) AS id';
        $this->addField('id', 'int4');
        $this->pk_fields    = array('id');
    }
}

class CnxEntity extends \Pomm\Object\BaseObject
{
}

class CnxOtherEntity extends \Pomm\Object\BaseObject
{
}


