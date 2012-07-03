<?php

namespace Pomm\Test\Identity;

use Pomm\Connection\Database;
use Pomm\Object\BaseObject;
use Pomm\Object\BaseObjectMap;
use Pomm\Exception\Exception;
use Pomm\Identity;

class IdentityMapTest extends \PHPUnit_Framework_TestCase
{
    protected static $database;

    protected static function createConnection(Identity\IdentityMapperInterface $imap = null)
    {
        return static::$database->createConnection($imap);
    }

    public static function setUpBeforeClass()
    {
        static::$database = new Database(array('dsn' => $GLOBALS['dsn'], 'name' => 'test_db'));

        $connection = static::$database->createConnection();

        $connection->begin();
        try
        {
            $sql = 'CREATE SCHEMA pomm_test';
            $connection->executeAnonymousQuery($sql);

            $map = $connection->getMapFor('Pomm\Test\Identity\Entity');
            $map->createTable();

            $connection->commit();
        }
        catch (Exception $e)
        {
            $connection->rollback();

            throw $e;
        }
    }

    public static function tearDownAfterClass()
    {
        $sql = 'DROP SCHEMA pomm_test CASCADE';
        static::$database->createConnection()
            ->executeAnonymousQuery($sql);
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

        $entity1 = new Entity(array('some_str' => md5('plop')));
        $entity3 = new Entity(array('some_str' => md5('plop')));
        $map->saveOne($entity1);
        $map->saveOne($entity3);

        $ts = $entity1['created_at'];
        unset($entity1['created_at']);
        $entity1['some_str'] = md5('pika');

        $entity2 = $map->findByPk(array('id' => $entity1['id']));

        $this->assertTrue($entity1 == $entity2, "Instances of the same entity have the same values.");
        $this->assertTrue($entity1 === $entity2, "Instances of the same entity are the same.");
        $this->assertEquals(md5('pika'), $entity2['some_str'], "'some_str' has been kept.");
        $this->assertTrue(is_null($entity2['created_at']), "'created_at' has NOT been synced from the database.");
        $this->assertFalse($entity3 === $entity2, "Instances of different entities are different.");
    }

    public function testSmart()
    {
        $map = static::createConnection(new Identity\IdentityMapperSmart())
            ->getMapFor('Pomm\Test\Identity\Entity');
        $map->truncateTable();

        $entity1 = new Entity(array('some_str' => md5('plop')));

        $map->saveOne($entity1);
        $entity1['some_str'] = md5('pika');
        $ts = $entity1['created_at'];
        unset($entity1['created_at']);

        $entity2 = $map->findByPk(array('id' => $entity1['id']));

        $this->assertTrue($entity1 == $entity2, "Instances of the same entity have the same values.");
        $this->assertTrue($entity1 === $entity2, "Instances of the same entity are the same.");
        $this->assertTrue(isset($entity1['created_at']), "'created_at' has been synced from the database.");
        $this->assertEquals($ts, $entity1['created_at'], "'created_at' is the right value.");
        $this->assertEquals(md5('pika'), $entity2['some_str'], "'some_str' has been kept.");

        $map->deleteOne($entity2);

        $entity3 = new Entity($entity2->extract());
        $entity3['some_str'] = md5('plop');
        $map->saveOne($entity3);

        $this->assertTrue($map->findByPk(array('id' => $entity2['id'])) === $entity3, "Re used 'id' from entity is not bound to old entity.");
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
    }
} 

class Entity extends BaseObject
{
}
