<?php

namespace Pomm\Test\IdentityMap;

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

            $map = $connection->getMapFor('Pomm\Test\IdentityMap\Entity');
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
        static::$connection->executeAnonymousQuery($sql);

        !is_null(static::$logger) && print_r(static::$logger);
    }

    public function testNone()
    {
        $map = static::createConnection(new Identity\IdentityMapperNone())
            ->getMapFor('Pomm\Test\Identity\Entity');
        $map->truncateTable();

        $entity1 = $map->saveOne(new Entity(array('some_str' => md5('plop'))));
        $entity2 = $map->findByPk(array('id' => $entity1['id']));
    }
}

class EntityMap extends BaseObjectMap
{
    protected function initialize()
    {
        $this->object_class = '\Pomm\Test\IdentityMap\Entity';
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
