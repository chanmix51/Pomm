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
        static::$connection->rollback();
        try
        {
            static::$connection->executeAnonymousQuery("DROP SCHEMA pomm_test CASCADE");
        }
        catch(ConnectionException $e)
        {
        }
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
        static::$connection->executeAnonymousQuery("SELECT 'pikachu'");
        $this->assertFalse(static::$connection->isInTransaction(), "We are NOT in a transaction.");
        static::$connection->begin();
        $this->assertTrue(static::$connection->isInTransaction(), "We ARE in a transaction.");
        $this->assertEquals(\PGSQL_TRANSACTION_INTRANS, static::$connection->getTransactionStatus(), "In a valid transaction, status 'idle'.");
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
            static::$connection
                ->rollback('useless') //fail the current transaction
                ;
            $this->fail("Rollback to unknown savepoint must raise a ConnectionException (none caught).");
        }
        catch (ConnectionException $e)
        {
            $this->assertTrue(true, "Rollback to unknown savepoint must raise a ConnectionException.");
        }
        catch(\Exception $e)
        {
            static::$connection->rollback();
            $this->fail(sprintf("Rollback to unknown savepoint must raise a ConnectionException ('%s' caught with message «%s»).", get_class($e), $e->getMessage()));
        }

        $this->assertTrue(static::$connection->isInTransaction(), "We ARE STILL in a transaction after failing query.");
        $this->assertEquals(\PGSQL_TRANSACTION_INERROR, static::$connection->getTransactionStatus(), "Transaction status is ERROR");
        static::$connection->commit(); //rollback
        $this->assertFalse(static::$connection->isInTransaction(), "We are NOT in a transaction after rollback.");
        $stmt = static::$connection->executeAnonymousQuery("SELECT count(*) AS plop_count FROM pomm_test.plop");
        $this->assertEquals(2, pg_fetch_result($stmt, 0), "We have 2 results.");

        try
        {
            static::$connection->executeAnonymousQuery("SELECT count(*) AS plop_count FROM pomm_test.plip");
            $this->fail("Table does not exist, query should fail.");
        }
        catch(\Pomm\Exception\ConnectionException $e)
        {
            $this->assertTrue(true, "Table does not exis, query should fail.");
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
            $this->assertTrue(false, sprintf("No exception thrown on valid sql statements. (%s caught).", get_class($e)));
        }
    }

    public function testSetDeferred()
    {
        $map = static::$connection->getMapFor('\Pomm\Test\Connection\FkEntity');
        $map->setUp();
        try
        {
            static::$connection
                ->begin()
                ->setConstraints(array('fk_entity_parent_id_fkey'), 'pomm_test')
                ;
            $this->assertTrue(static::$connection->isTransactionValid(), "Transaction status is OK after deferring fkeys.");

            $entity1 = $map->createAndSaveObject(array('id' => 1, 'parent_id' => 2, 'some_data' => 3));
            $entity2 = $map->createAndSaveObject(array('id' => 2, 'parent_id' => null, 'some_data' => 2));
            $this->assertTrue(static::$connection->isTransactionValid(), "Transaction status is OK after inserting data with deferred fkeys.");
            $this->assertEquals(2, $map->findAll()->count(), 'We have two rows in this table.');
        }
        catch (\Exception $e)
        {
            static::$connection->rollback();
            $map->dropOut();

            throw $e;
        }
        static::$connection->commit();
        $map->alterConstraint();

        try
        {
            static::$connection
                ->begin()
                ->setConstraints(array('fk_entity_parent_id_fkey'), 'pomm_test', Connection::CONSTRAINTS_IMMEDIATE)
                ;
            $entity3 = $map->createAndSaveObject(array('id' => 3, 'parent_id' => 4, 'some_data' => 3));
            static::$connection->rollback();
            $map->dropOut();
            $this->fail('Immediate constraint checking should have made above query to throw a SqlException (none caught).');
        }
        catch(SqlException $e)
        {
            $this->assertTrue(true, 'Immediate constraint checking should have made above query to throw a SqlException.');
        }
        catch(\Exception $e)
        {
            static::$connection->rollback();
            $map->dropOut();
            $this->fail(sprintf("Immediate constraint checking should have made above query to throw a SqlException ('%s' caught).", get_class($e)));
        }
        static::$connection->rollback();
        $map->dropOut();
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

class FkEntityMap extends \Pomm\Object\BaseObjectMap
{
    protected function initialize()
    {
        $this->object_class =  'Pomm\Test\Connection\FkEntity';
        $this->object_name  =  'pomm_test.fk_entity';
        $this->addField('id', 'int4');
        $this->addField('parent_id', 'int4');
        $this->addField('some_data', 'int4');
        $this->pk_fields    = array('id');
    }

    public function setUp()
    {
        $sql = 'create schema pomm_test; create table pomm_test.fk_entity (id serial primary key, parent_id int references pomm_test.fk_entity (id) on delete cascade deferrable, some_data text not null);';
        $this->connection->executeAnonymousQuery($sql);
    }

    public function dropOut()
    {
        $sql = 'drop schema pomm_test cascade';
        $this->connection->executeAnonymousQuery($sql);
    }

    public function alterConstraint()
    {
        $sql = <<<SQL
alter table pomm_test.fk_entity drop constraint fk_entity_parent_id_fkey;
alter table pomm_test.fk_entity add constraint fk_entity_parent_id_fkey foreign key (parent_id) references pomm_test.fk_entity (id) on delete cascade deferrable initially deferred;
SQL;
        $this->connection->executeAnonymousQuery($sql);
    }
}

class FkEntity extends \Pomm\Object\BaseObject
{
}

