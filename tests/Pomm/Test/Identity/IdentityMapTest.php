<?php

namespace Pomm\Test\Identity;

use Pomm\Connection\Database;
use Pomm\Connection\Service;
use Pomm\Object\BaseObject;
use Pomm\Object\BaseObjectMap;
use Pomm\Exception\Exception;
use Pomm\Exception\ConnectionException;
use Pomm\Identity;

class IdentityMapTest extends \PHPUnit_Framework_TestCase
{
    protected static $database;
    protected static $service;

    protected static function createConnection(Identity\IdentityMapperInterface $imap = null)
    {
        return static::$database->createConnection($imap);
    }

    public static function setUpBeforeClass()
    {
        static::$database = new Database(array('dsn' => $GLOBALS['dsn'], 'name' => 'test_db'));
        $connection = static::$database->createConnection();
        static::$service = new IdentityMapService($connection);
        static::$service->createSchema();
    }

    public static function tearDownAfterClass()
    {
        static::$service->dropSchema();
    }

    public function testNone()
    {
        $map = static::createConnection(new Identity\IdentityMapperNone())
            ->getMapFor('Pomm\Test\Identity\Entity');
        $map->truncateTable();

        $entity1 = new Entity(array('some_str' => md5('plop')));
        $map->saveOne($entity1);

        $entity2 = $map->findByPk(array('id' => $entity1['id']));

        $this->assertTrue($entity1 == $entity2, "Instances of the same entity have the same values.");
        $this->assertFalse($entity1 === $entity2, "Instances of the same entity are NOT the same.");
    }

    public function testStrict()
    {
        $map = static::createConnection(new Identity\IdentityMapperStrict())
            ->getMapFor('Pomm\Test\Identity\Entity');
        $map->truncateTable();

        $entity1 = new Entity(array('some_str' => 'entity 1'));
        $entity2 = new Entity(array('some_str' => 'entity 2'));
        $map->saveOne($entity1);
        $map->saveOne($entity2);

        $ts = $entity1['created_at'];
        unset($entity1['created_at']);
        $entity1['some_str'] = 'entity 1 modified';

        $entity1_bis = $map->findByPk(array('id' => $entity1['id']));

        $this->assertEquals($entity1, $entity1_bis, "Instances of the same entity have the same values.");
        $this->assertTrue($entity1 === $entity1_bis, "Instances of the same entity are the same.");
        $this->assertEquals('entity 1 modified', $entity1_bis['some_str'], "'some_str' has been kept.");
        $this->assertTrue(!$entity1_bis->hasCreatedAt(), "'created_at' has NOT been synced from the database.");
        $this->assertFalse($entity2 === $entity1_bis, "Instances of different entities are different.");
    }

    public function testSmart()
    {
        $map = static::createConnection(new Identity\IdentityMapperSmart())
            ->getMapFor('Pomm\Test\Identity\Entity');
        $map->truncateTable();

        $entity1 = new Entity(array('some_str' => 'entity 1'));

        $map->saveOne($entity1);
        $entity1['some_str'] = 'entity 1 modified';
        $ts = $entity1['created_at'];
        unset($entity1['created_at']);

        $entity1_bis = $map->findByPk(array('id' => $entity1['id']));

        $this->assertEquals($entity1, $entity1_bis, "Instances of the same entity have the same values.");
        $this->assertTrue($entity1 === $entity1_bis, "Instances of the same entity are the same.");
        $this->assertTrue($entity1_bis->hasCreatedAt(), "'created_at' has been synced from the database.");
        $this->assertEquals($ts, $entity1['created_at'], "'created_at' is the right value.");
        $this->assertEquals('entity 1 modified', $entity1_bis['some_str'], "'some_str' has been kept.");

        $map->deleteOne($entity1_bis);

        $entity2 = new Entity($entity1_bis->extract());
        $entity2['some_str'] = 'new entity 1';
        $map->saveOne($entity2);

        $this->assertTrue($map->findByPk(array('id' => $entity1_bis['id'])) === $entity2, "Re used 'id' from entity is not bound to old entity.");
    }
}


class EntityMap extends BaseObjectMap
{
    protected function initialize()
    {
        $this->object_class = '\Pomm\Test\Identity\Entity';
        $this->object_name  = 'pomm_test.entity';

        $this->addField('id', 'int4');
        $this->addField('some_str', 'char');
        $this->addField('created_at', 'timestamp');

        $this->pk_fields = array('id');
    }

    public function createTable()
    {
        $sql = sprintf('CREATE TABLE %s (id serial PRIMARY KEY, some_str char(32) NOT NULL, created_at timestamp NOT NULL DEFAULT now())', $this->getTableName());
        $this->connection->executeAnonymousQuery($sql);
    }

    public function truncateTable()
    {
        $sql = sprintf('TRUNCATE %s', $this->getTableName());
        $this->connection->executeAnonymousQuery($sql);

        $sql = sprintf('ALTER SEQUENCE %s_id_seq RESTART', $this->getTableName());
        $this->connection->executeAnonymousQuery($sql);
    }
} 

class Entity extends BaseObject
{
}

class IdentityMapService extends Service
{
    public function createSchema()
    {
        $this->begin();
        try
        {
            $sql = 'create schema pomm_test';
            $this->connection->executeAnonymousQuery($sql);

            $map = $this->connection
                ->getMapFor('Pomm\Test\Identity\Entity')
                ->createTable();

            $this->commit();
        }
        catch (ConnectionException $e)
        {
            $this->rollback();

            throw $e;
        }
    }

    public function dropSchema()
    {
        $sql = 'DROP SCHEMA pomm_test CASCADE';
        $this->connection->executeAnonymousQuery($sql);
    }
}
