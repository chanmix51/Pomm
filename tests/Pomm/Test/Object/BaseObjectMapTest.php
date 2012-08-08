<?php

namespace Pomm\Test\Object;

use Pomm\Connection\Database;
use Pomm\Object\BaseObject;
use Pomm\Object\BaseObjectMap;
use Pomm\Exception\Exception;
use Pomm\Query\Where;

class BaseObjectMapTest extends \PHPUnit_Framework_TestCase
{
    protected static $map;
    protected static $logger;

    public static function setUpBeforeClass()
    {
        $database = new Database(array('dsn' => $GLOBALS['dsn'], 'name' => 'test_db'));

        if (isset($GLOBALS['dev']) && $GLOBALS['dev'] == 'true') 
        {
            static::$logger = new \Pomm\Tools\Logger();

            static::$map = $database
                ->createConnection()
                ->registerFilter(new \Pomm\FilterChain\LoggerFilter(static::$logger))
                ->getMapFor('Pomm\Test\Object\BaseEntity');
        } 
        else 
        {
            static::$map = $database
                ->createConnection()
                ->getMapFor('Pomm\Test\Object\BaseEntity');
        }

        static::$map->createTable();
    }

    public static function tearDownAfterClass()
    {
        static::$map->dropTable();

        !is_null(static::$logger) && print_r(static::$logger);
    }

    public function testHydrate()
    {
        $entity = static::$map->createObject();
        $this->assertInstanceOf('Pomm\Test\Object\BaseEntity', $entity, "Entity is a 'Pomm\\Test\Object\\BaseEntity' instance.");
        $this->assertTrue($entity->isNew(), 'Entity is new.');

        return $entity;
    }

    /**
     * @depends testHydrate
     **/
    public function testSave(BaseEntity $entity)
    {
        $entity['some_data'] = 'plop';
        static::$map->saveOne($entity);

        $this->assertTrue((boolean) ($entity->_getStatus() & BaseObject::EXIST), "Object now exists in database.");
        $this->assertFalse($entity->isModified(), "Entity has not been modified since last persist operation.");
        $this->assertEquals(1, $entity['id'], "Entity has an ID.");
        $this->assertEquals('plop', $entity['some_data'], "'some_data' is unchanged.");
        $this->assertFalse($entity['bool_data'], "Bool data has been added.");

        return $entity;
    }

    /**
     * @depends testSave 
     **/
    public function testSaveUpdate(BaseEntity $entity)
    {
        $entity['some_data'] = 'pika chu';
        $entity['bool_data'] = true;
        static::$map->saveOne($entity);
        $this->assertTrue((boolean) ($entity->_getStatus() & BaseObject::EXIST), "Object exists in database.");
        $this->assertFalse($entity->isModified(), "Entity has not been modified since last persist operation.");
        $this->assertEquals(1, $entity['id'], "Entity has an ID.");
        $this->assertEquals('pika chu', $entity['some_data'], "'some_data' has been updated.");
        $this->assertTrue($entity['bool_data'], "'bool_data' has been updated.");

        return $entity;
    }

    /**
     * @depends testSaveUpdate
     **/
    public function testUpdate(BaseEntity $entity)
    {
        $entity['some_data'] = 'some other data';
        $entity['bool_data'] = false;
        static::$map->updateOne($entity, array('some_data'));
        $this->assertTrue((boolean) ($entity->_getStatus() & BaseObject::EXIST), "Object exists in database.");
        $this->assertFalse($entity->isModified(), "Entity has not been modified since last persist operation.");
        $this->assertEquals(1, $entity['id'], "Entity has an ID.");
        $this->assertEquals('some other data', $entity['some_data'], "'some_data' has been updated.");
        $this->assertTrue($entity['bool_data'], "'bool_data' has been overwritten with database value.");

        return $entity;
    }

    /**
     * @depends testUpdate
     **/
    public function testFindWhere(BaseEntity $entity)
    {
        $test_entity = static::$map->findWhere('some_data = ?', array($entity['some_data']), 'ORDER BY id DESC LIMIT 1')->current();
        $this->assertNotSame($entity, $test_entity, "Entities are not the same instance 'check ident mapper'.");
        $this->assertEquals($entity['id'], $test_entity['id'], "Entities have the same id.");

        $test_entity = static::$map->findWhere(Where::create('some_data = ?', array($entity['some_data'])), null, 'ORDER BY id DESC LIMIT 1')->current();
        $this->assertNotSame($entity, $test_entity, "Entities are not the same instance 'check ident mapper'.");
        $this->assertEquals($entity['id'], $test_entity['id'], "Entities have the same id.");


        return $entity;
    }


    /**
     * @depends testFindWhere 
     **/
    public function testFindByPK(BaseEntity $entity)
    {
        $test_entity = static::$map->findByPk(array('id' => $entity['id']));
        $this->assertTrue($test_entity == $entity, "Entities have the same values.");
        $this->assertNotSame($entity, $test_entity, "Entities are not the same instance.");

        return $entity;
    }

    /**
     * @depends testFindByPK
     **/
    public function testQuery(BaseEntity $entity)
    {
        $sql = "SELECT %s FROM %s WHERE plop.id = ?";
        $sql = sprintf($sql,
            join(', ', static::$map->getSelectFields('plop')),
            static::$map->getTableName('plop')
        );

        $collection = static::$map->query($sql, array(1), 'ORDER BY id DESC LIMIT 1');
        $this->assertInstanceOf('\Pomm\Object\Collection', $collection, "Query returns 'Collection' instance.");

        $collection = static::$map->query($sql, array(0), 'ORDER BY id DESC LIMIT 1');
        $this->assertInstanceOf('\Pomm\Object\Collection', $collection, "Query returns 'Collection' instance with no results.");

        return $entity;
    }

    /**
     * @depends testQuery
     **/
    public function testDelete(BaseEntity $entity)
    {
        static::$map->deleteOne($entity);
        $this->assertFalse((bool) ($entity->_getStatus() & BaseObject::EXIST), "After deletion, object is not marked as EXIST.");
        $this->assertTrue(is_null(static::$map->findByPk(array('id' => $entity['id']))), "Object does not exist in the DB anymore.");

        return $entity;
    }

    /**
     * @depends testDelete
     **/
    public function testChangePrimaryKey(BaseEntity $entity)
    {
        static::$map->changeToMultiplePrimaryKey();
        $entity = static::$map->createObject(array('name' => 'plop', 'some_data' => 'plop'));

        static::$map->saveOne($entity);
        $this->assertEquals(array('id' => 2, 'name' => 'plop'), $entity->get(static::$map->getPrimaryKey()), "Primary key is retrieved.");

        $entity['bool_data'] = true;
        static::$map->saveOne($entity);

        $this->assertTrue($entity['bool_data'], "'bool_data' is updated.");
        $this->assertEquals(array('id' => 2, 'name' => 'plop'), $entity->get(static::$map->getPrimaryKey()), "Primary key has not changed.");

        $entity['some_data'] = 'other data';
        static::$map->updateOne($entity, array('some_data'));

        $this->assertEquals('other data', $entity['some_data'], "'some_data' has been updated.");

        static::$map->deleteOne($entity);
        $this->assertTrue(is_null(static::$map->findByPk($entity->get(static::$map->getPrimaryKey()))), "Object does not exist in the DB anymore.");
    }

}

class BaseEntityMap extends BaseObjectMap
{
    protected function initialize()
    {
        $this->object_class =  'Pomm\Test\Object\BaseEntity';
        $this->object_name  =  'pomm_test.base_entity';
        $this->addField('id', 'int4');
        $this->addField('some_data', 'varchar');
        $this->addField('bool_data', 'bool');
        $this->pk_fields    = array('id');
    }

    public function createTable()
    {
        try {
            $this->connection->begin();
            $sql = "CREATE SCHEMA pomm_test";
            $this->connection->executeAnonymousQuery($sql);
            $sql = sprintf("CREATE TABLE %s (id serial PRIMARY KEY, some_data varchar NOT NULL, bool_data boolean NOT NULL DEFAULT false)", $this->getTableName());
            $this->connection->executeAnonymousQuery($sql);
            $this->connection->commit();
        } catch (Exception $e) {
            $this->connection->rollback();
            throw $e;
        }
    }

    public function dropTable()
    {
        $sql = "DROP SCHEMA pomm_test CASCADE";
        $this->connection->executeAnonymousQuery($sql);
    }

    public function changeToMultiplePrimaryKey()
    {
        $sql = sprintf('TRUNCATE TABLE %s', $this->getTableName());
        $this->connection->executeAnonymousQuery($sql);

        $this->changeToNoPrimaryKey();

        $sql = sprintf('ALTER TABLE %s ADD COLUMN name varchar NOT NULL', $this->getTableName());;
        $this->connection->executeAnonymousQuery($sql);

        $sql = sprintf('ALTER TABLE %s ADD PRIMARY KEY (id, name)', $this->getTableName());
        $this->connection->executeAnonymousQuery($sql);

        $this->addField('name', 'varchar');
        $this->pk_fields = array('id', 'name');
    }

    public function changeToNoPrimaryKey()
    {
        $sql = sprintf('ALTER TABLE %s DROP CONSTRAINT base_entity_pkey', $this->getTableName());;
        $this->connection->executeAnonymousQuery($sql);
    }

}

class BaseEntity extends BaseObject
{
}

