<?php

namespace Pomm\Test\Connection;

use Pomm\Connection\Database;
use Pomm\Exception\Exception;
use Pomm\Converter;
use Pomm\Type;
use Pomm\Query\PreparedQuery;

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

    public function testTransaction()
    {
        $this->assertFalse(static::$connection->isInTransaction(), "We are NOT in a transaction.");
        static::$connection->begin();
        $this->assertTrue(static::$connection->isInTransaction(), "We ARE in a transaction.");
        $this->assertEquals(\PGSQL_TRANSACTION_INTRANS, static::$connection->getTransactionStatus(), "Transaction status is OK");
        static::$connection->executeAnonymousQuery("CREATE SCHEMA pomm_test");
        static::$connection->executeAnonymousQuery("CREATE TABLE pomm_test.plop(pika serial, chu char)");

        static::$connection->setSavepoint('schema');
        $this->assertTrue(static::$connection->isInTransaction(), "We ARE in a transaction after savepoint.");
        static::$connection->executeAnonymousQuery("INSERT INTO pomm_test.plop (chu) VALUES ('a'), ('b')");
        $stmt = static::$connection->executeAnonymousQuery("SELECT count(*) AS plop_count FROM pomm_test.plop");
        $this->assertEquals(2, pg_fetch_result($stmt, 0), "We have 2 results.");

        static::$connection->rollback('schema');
        $this->assertTrue(static::$connection->isInTransaction(), "We ARE in a transaction after rollback to savepoint.");
        $stmt = static::$connection->executeAnonymousQuery("SELECT count(*) AS plop_count FROM pomm_test.plop");
        $this->assertEquals(0, pg_fetch_result($stmt, 0), "We have 0 results.");

        static::$connection->setSavepoint('useless');
        static::$connection->executeAnonymousQuery("INSERT INTO pomm_test.plop (chu) VALUES ('c'), ('d')");
        static::$connection->commit();
        $this->assertFalse(static::$connection->isInTransaction(), "We are NOT in a transaction after commit.");
        $stmt = static::$connection->executeAnonymousQuery("SELECT count(*) AS plop_count FROM pomm_test.plop");
        $this->assertEquals(2, pg_fetch_result($stmt, 0), "We have 2 results.");

        static::$connection->begin();
        try
        {
            static::$connection->rollback('useless'); //fail the current transaction
            $this->fail("Rollback to unknown savepoint must raise an exception (none caught).");
        }
        catch (\Pomm\Exception\SqlException $e)
        {
            $this->assertTrue(true, "Rollback to unknown savepoint must raise a SqlException.");
        }
        catch(\Exception $e)
        {
            $this->fail("Rollback to unknown savepoint must raise an exception ('%s' caught).", get_class($e));
        }

        $this->assertTrue(static::$connection->isInTransaction(), "We ARE STILL in a transaction after failing query.");
        $this->assertEquals(\PGSQL_TRANSACTION_INERROR, static::$connection->getTransactionStatus(), "Transaction status is ERROR");
        static::$connection->commit(); //rollback
        $this->assertFalse(static::$connection->isInTransaction(), "We are NOT in a transaction after rollback.");
        $stmt = static::$connection->executeAnonymousQuery("SELECT count(*) AS plop_count FROM pomm_test.plop");
        $this->assertEquals(2, pg_fetch_result($stmt, 0), "We have 2 results.");

        static::$connection->begin();
        static::$connection->executeAnonymousQuery("DROP SCHEMA pomm_test CASCADE");
        static::$connection->commit();
        try
        {
            static::$connection->executeAnonymousQuery("SELECT count(*) AS plop_count FROM pomm_test.plop");
            $this->fail("Table does not exist anymore, query should fail.");
        }
        catch(\Pomm\Exception\SqlException $e)
        {
            $this->assertTrue(true, "Table does not exist anymore, query should fail.");
        }
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
            $this->assertTrue(false, 'Failed anonymous queries should throw an exception (no exception caught).');
        }
        catch (\Pomm\Exception\Exception $e)
        {
            $this->assertTrue(true, 'Failed anonymous queries should throw an exception.');
        }
        catch (\Exception $e)
        {
            $this->assertTrue(false, sprintf("Failed anonymous queries should throw an exception (%s caught).", get_class($e)));
        }

        try
        {
            static::$connection->executeAnonymousQuery('select true');
            $this->assertTrue(true, 'No exception thrown on valid sql statements.');
        }
        catch (\Exception $e)
        {
            $this->assertTrue(false, sprintf("No exception thrown on valid sql statements. (%s caught).", get_class($e)));
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
