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

        if (isset($GLOBALS['dev']) && $GLOBALS['dev'] == 'true') {
            static::$logger = new \Pomm\Tools\Logger();

            static::$map = $database
                ->createConnection()
                ->registerFilter(new \Pomm\FilterChain\LoggerFilter(static::$logger))
                ->getMapFor('Pomm\Test\Object\BaseEntity');
        } else {
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
        $entity['some data'] = 'plop';
        static::$map->saveOne($entity);

        $this->assertTrue((boolean) ($entity->_getStatus() & BaseObject::EXIST), "Object now exists in database.");
        $this->assertFalse($entity->isModified(), "Entity has not been modified since last persist operation.");
        $this->assertEquals(1, $entity['id'], "Entity has an ID.");
        $this->assertEquals('plop', $entity['some data'], "'some data' is unchanged.");
        $this->assertFalse($entity['bool_data'], "Bool data has been added.");

        $another_entity = static::$map->createAndSaveObject(Array('some data' => 'more plop'));
        $this->assertTrue((boolean) ($another_entity->_getStatus() & BaseObject::EXIST), "Object now exists in database.");
        $this->assertEquals('more plop', $another_entity['some data'], "'some data' is unchanged.");

        $this->assertTrue($another_entity->has('ts_data'), "'ts_data' exists even when null.");
        $this->assertTrue(is_null($another_entity['ts_data']), "'ts_data' is null.");

        return $entity;
    }

    /**
     * @depends testSave
     **/
    public function testSaveUpdate(BaseEntity $entity)
    {
        $entity['some data'] = 'pika chu';
        $entity['bool_data'] = true;
        static::$map->saveOne($entity);
        $this->assertTrue((boolean) ($entity->_getStatus() & BaseObject::EXIST), "Object exists in database.");
        $this->assertFalse($entity->isModified(), "Entity has not been modified since last persist operation.");
        $this->assertEquals(1, $entity['id'], "Entity has an ID.");
        $this->assertEquals('pika chu', $entity['some data'], "'some data' has been updated.");
        $this->assertTrue($entity['bool_data'], "'bool_data' has been updated.");

        return $entity;
    }

    /**
     * @depends testSaveUpdate
     **/
    public function testUpdate(BaseEntity $entity)
    {
        $entity['some data'] = 'some other data';
        $entity['bool_data'] = false;
        $entity['ts_data'] = new \DateTime('2012-09-12 00:09:42.123456');
        static::$map->updateOne($entity, array('some data', 'ts_data'));
        $this->assertTrue((boolean) ($entity->_getStatus() & BaseObject::EXIST), "Object exists in database.");
        $this->assertFalse($entity->isModified(), "Entity has not been modified since last persist operation.");
        $this->assertEquals(1, $entity['id'], "Entity has an ID.");
        $this->assertEquals('some other data', $entity['some data'], "'some data' has been updated.");
        $this->assertTrue($entity['bool_data'], "'bool_data' has been overwritten with database value.");
        $this->assertEquals('2012-09-12 00:09:42.123456', $entity['ts_data']->format('Y-m-d H:i:s.u'), "Timestamp is set.");

        return $entity;
    }

    /**
     * @depends testUpdate
     **/
    public function testFindWhere(BaseEntity $entity)
    {
        $test_entity = static::$map->findWhere('"some data" = ?', array($entity['some data']), 'ORDER BY id DESC LIMIT 1')->current();
        $this->assertNotSame($entity, $test_entity, "Entities are not the same instance 'check ident mapper'.");
        $this->assertEquals($entity['id'], $test_entity['id'], "Entities have the same id.");

        $test_entity = static::$map->findWhere(Where::create('"some data" = ?', array($entity['some data'])), null, 'ORDER BY id DESC LIMIT 1')->current();
        $this->assertNotSame($entity, $test_entity, "Entities are not the same instance 'check ident mapper'.");
        $this->assertEquals($entity['id'], $test_entity['id'], "Entities have the same id.");

        $test_entity = static::$map->findWhere('ts_data > ?', array(new \DateTime("2000-01-01")))->current();
        $this->assertEquals(1, $test_entity['id'], "Can feed findWhere with a DateTime instance.");

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
    public function testUpdateByPk(BaseEntity $entity)
    {
        $dt = new \DateTime();

        $entity = static::$map->updateByPk($entity->get(static::$map->getPrimaryKey()), array('ts_data' => $dt, 'bool_data' => false));

        $this->assertTrue(!$entity->isModified(), "Object is not marked as modified.");
        $this->assertTrue(!$entity['bool_data'], "Bool data is false.");
        $this->assertEquals($dt->format('U'), $entity['ts_data']->format('U'), "Timestamps are equals.");

        return $entity;
    }

    /**
     * @depends testUpdateByPk
     **/
    public function testDelete(BaseEntity $entity)
    {
        static::$map->deleteOne($entity);
        $this->assertFalse((bool) ($entity->_getStatus() & BaseObject::EXIST), "After deletion, object is not marked as EXIST.");
        $this->assertTrue(is_null(static::$map->findByPk(array('id' => $entity['id']))), "Object does not exist in the DB anymore.");

        return $entity;
    }

    /**
     * @depends testUpdateByPk
     **/
    public function testGetRemoteSelectFields()
    {
        $fields = static::$map->getRemoteSelectFields('plop');
        $fields = join(', ', array_map(function($alias, $field) { return sprintf('%s AS "%s"', $field, $alias); }, array_keys($fields), $fields));
        $this->assertEquals('plop."id" AS "pomm_test.base_entity{id}", plop."some data" AS "pomm_test.base_entity{some data}", plop."bool_data" AS "pomm_test.base_entity{bool_data}", plop."ts_data" AS "pomm_test.base_entity{ts_data}"', $fields, "Remote fields are ok");
    }

    /**
     * @depends testDelete
     **/
    public function testChangePrimaryKey(BaseEntity $entity)
    {
        static::$map->changeToMultiplePrimaryKey();
        $entity = static::$map->createAndSaveObject(array('name' => 'plop', 'some data' => 'plop'));

        $this->assertEquals(array('id' => 3, 'name' => 'plop'), $entity->get(static::$map->getPrimaryKey()), "Primary key is retrieved.");

        $entity['bool_data'] = true;
        static::$map->saveOne($entity);

        $this->assertTrue($entity['bool_data'], "'bool_data' is updated.");
        $this->assertEquals(array('id' => 3, 'name' => 'plop'), $entity->get(static::$map->getPrimaryKey()), "Primary key has not changed.");

        $entity['some data'] = 'other data';
        static::$map->updateOne($entity, array('some data'));

        $this->assertEquals('other data', $entity['some data'], "'some data' has been updated.");

        static::$map->deleteOne($entity);
        $this->assertTrue(is_null(static::$map->findByPk($entity->get(static::$map->getPrimaryKey()))), "Object does not exist in the DB anymore.");

        return $entity;
    }

    /**
     * @depends testChangePrimaryKey
     **/
    public function testFindAll(BaseEntity $entity)
    {
        static::$map->insertSomeData();
        $raw_res = static::$map->findAll();
        $ordered_res = static::$map->findAll('ORDER BY name ASC');
        $limited_res = static::$map->findAll('LIMIT 3');

        $this->assertEquals(5, $raw_res->count(), "5 results.");

        $this->assertEquals(5, $ordered_res->count(), "5 results.");
        foreach ($ordered_res as $index => $result) {
            $this->assertEquals( 5 - $index, $result['id'], "Names are the other way than ids.");
        }

        $this->assertEquals(3, $limited_res->count(), "We have 3 results.");
    }
}

class BaseEntityMap extends BaseObjectMap
{
    protected function initialize()
    {
        $this->object_class =  'Pomm\Test\Object\BaseEntity';
        $this->object_name  =  'pomm_test.base_entity';
        $this->addField('id', 'int4');
        $this->addField('some data', 'varchar');
        $this->addField('bool_data', 'bool');
        $this->addField('ts_data', 'timestamp');
        $this->pk_fields    = array('id');
    }

    public function createTable()
    {
        try {
            $this->connection->begin();
            $sql = "CREATE SCHEMA pomm_test";
            $this->connection->executeAnonymousQuery($sql);
            $sql = sprintf("CREATE TABLE %s (id serial PRIMARY KEY, \"some data\" varchar NOT NULL, bool_data boolean NOT NULL DEFAULT false, ts_data timestamp)", $this->getTableName());
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

    public function insertSomeData()
    {
        $sql = sprintf('TRUNCATE TABLE %s', $this->getTableName());
        $this->connection->executeAnonymousQuery($sql);

        $sql = sprintf("INSERT INTO %s (id, name, \"some data\", ts_data) VALUES (1, 'echo', 'data', '1975-06-29 21:15:43.123456'), (4, 'bravo', 'data', null), (3, 'charly', 'data', '1986-12-21 18:32:45.123456'), (2, 'dingo', 'data', '1993-06-29 02:45:33.123456'), (5, 'alpha', 'data', '2007-09-08 04:01:00.000000')", $this->getTableName());
        $this->connection->executeAnonymousQuery($sql);
    }
}

class BaseEntity extends BaseObject
{
}
