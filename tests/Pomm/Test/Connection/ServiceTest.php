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
use Pomm\Connection\Service;

class ServiceTest extends \PHPUnit_Framework_TestCase
{
    protected static $service;
    protected static $map;

    public static function setUpBeforeClass()
    {
        $database = new Database(array('dsn' => $GLOBALS['dsn'], 'name' => 'test_db'));
        $connection = $database->createConnection();
        static::$service = new MyService($connection);
        static::$service->createSchema();
        static::$map = $connection->getMapFor('Pomm\Test\Connection\FkEntity');
    }

    public static function tearDownAfterClass()
    {
        static::$service->rollbackTransaction();
        static::$service->dropSchema();
    }

    public function setUp()
    {
        static::$map->setUp();
    }

    public function tearDown()
    {
        static::$service->rollbackTransaction();
        static::$map->dropOut();
    }

    public function testBeginCommit()
    {
        $this->assertFalse(static::$service->isInTransaction(), 'We are NOT in a transaction before begin.');
        static::$service->startTransaction();
        $this->assertTrue(static::$service->isInTransaction(), 'We ARE in a transaction after begin.');
        static::$service->insertData(2);
        $this->assertEquals(2, static::$service->countData(), 'We have 2 records in the table in transaction.');
        $this->assertTrue(static::$service->isInTransaction(), 'We ARE in a transaction before commit.');
        static::$service->commitTransaction();
        $this->assertFalse(static::$service->isInTransaction(), 'We are NOT in a transaction after commit.');
        $this->assertEquals(2, static::$service->countData(), 'We have 2 records in the table in transaction.');
    }

    public function testBeginRollback()
    {
        static::$service
            ->startTransaction()
            ->insertData(2)
            ->rollbackTransaction();
        $this->assertFalse(static::$service->isInTransaction(), 'We are NOT in a transaction after rollback.');
        $this->assertEquals(0, static::$service->countData(), 'We have no records in the table after rollback.');
    }

    public function testSavepoints()
    {
        static::$service
            ->startTransaction()
            ->insertData(2)
            ->setSavepoint('test')
            ->insertData(2);
        $this->assertEquals(4, static::$service->countData(), 'We have 4 records in the table after savepoint.');
        static::$service->rollbackToSavepoint('test');
        $this->assertEquals(2, static::$service->countData(), 'We have 2 records in the table after rollback to savepoint.');
        static::$service->commitTransaction();
        $this->assertEquals(2, static::$service->countData(), 'We have 2 records in the table after commit (savepoint).');
    }

    public function testDeferrableConstraints()
    {
        static::$service
            ->startTransaction()
            ->deferKey()
            ->insertData(2)
            ->changeParentFor(1, 4);
        $this->assertTrue(static::$service->isInTransaction(), 'We are still in a transaction despite deferred computations.');
        static::$service
            ->insertData(2)
            ->commitTransaction();
        $this->assertEquals(4, static::$service->countData(), 'We have 4 records in the table after commit (deferrable).');
    }

    public function testFailTransaction()
    {
        static::$service
            ->startTransaction()
            ->insertData(2);
        $this->assertTrue(static::$service->isTransactionValid(), 'Transaction is valid.');
        try
        {
            static::$service->failTransaction();
            $this->fail('A failing query must throw a ConnectionException (none caught).');
        }
        catch(ConnectionException $e)
        {
        }
        $this->assertFalse(static::$service->isTransactionValid(), 'Transaction is NOT valid.');
        static::$service->commitTransaction();
        $this->assertEquals(0, static::$service->countData(), 'We have no data, transaction has been rollback.');
    }

    public function testFailTransactionDeferrable()
    {
        try
        {
            static::$service
                ->startTransaction()
                ->deferKey()
                ->insertData(2)
                ->changeParentFor(1, 4)
                ->commitTransaction();
            $this->fail('Commit a failed transaction must throw a ConnectionException (none caught).');
        }
        catch (ConnectionException $e)
        {
            $this->assertTrue(true, 'Commit a failed transaction must throw a ConnectionException.');
        }
        catch(\Exception $e)
        {
            $this->assertTrue(false, sprintf("Commit a failed transaction must throw a ConnectionException ('%s' caught).", get_class($e)));
        }
    }
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
        $sql = 'create table pomm_test.fk_entity (id serial primary key, parent_id int references pomm_test.fk_entity (id) on delete cascade deferrable, some_data text not null);';
        $this->connection->executeAnonymousQuery($sql);
    }

    public function dropOut()
    {
        $sql = 'drop table pomm_test.fk_entity cascade';
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

class MyService extends Service
{

    public function createSchema()
    {
        $this->connection->executeAnonymousQuery(sprintf("create schema pomm_test"));

        return $this;
    }

    public function dropSchema()
    {
        $this->connection->executeAnonymousQuery(sprintf("drop schema pomm_test cascade"));

        return $this;
    }

    public function startTransaction()
    {
        $this->begin();

        return $this;
    }

    public function commitTransaction()
    {
        $this->commit();

        return $this;
    }

    public function rollbackTransaction()
    {
        $this->rollback();

        return $this;
    }

    public function setSavepoint($name)
    {
        parent::setSavepoint($name);

        return $this;
    }

    public function rollbackToSavepoint($name)
    {
        $this->rollback($name);

        return $this;
    }

    public function isInTransaction()
    {
        return parent::isInTransaction();
    }

    public function isTransactionValid()
    {
        return parent::isTransactionValid();
    }

    public function deferKey()
    {
        $this->setConstraints(array('fk_entity_parent_id_fkey'), 'pomm_test', Connection::CONSTRAINTS_DEFERRED);

        return $this;
    }

    public function insertData($count)
    {
        $values = array();
        for ($i = 0; $i < $count; $i++)
        {
            $values[] = array('some_data' => $i);
        }

        $this->connection
            ->getMapFor('Pomm\Test\Connection\FkEntity')
            ->createAndSaveObjects($values);

        return $this;
    }

    public function changeParentFor($index, $parent_id)
    {
        $this->connection
            ->getMapFor('Pomm\Test\Connection\FkEntity')
            ->updateByPk(array('id' => $index), array('parent_id' => $parent_id));

        return $this;
    }

    public function countData()
    {
        $stmt = $this->connection->executeAnonymousQuery("select count(*) from pomm_test.fk_entity");

        return pg_fetch_result($stmt, 0);
    }

    public function failTransaction()
    {
        $stmt = $this->connection->executeAnonymousQuery("select this is an error");

        return $this;
    }
}
