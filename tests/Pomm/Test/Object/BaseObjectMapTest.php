<?php

namespace Pomm\Test\Object;

use Pomm\Connection\Database;
use Pomm\Connection\ModelLayer;
use Pomm\Object\BaseObject;
use Pomm\Object\BaseObjectMap;
use Pomm\Exception\Exception;
use Pomm\Exception\ConnectionException;
use Pomm\Query\Where;

class BaseObjectMapTest extends \PHPUnit_Framework_TestCase
{
    protected static $map;
    protected static $logger;
    protected static $service;

    public static function setUpBeforeClass()
    {
        $database = new Database(array('dsn' => $GLOBALS['dsn'], 'name' => 'test_db'));
        $connection = $database->getConnection();
        static::$map = $connection->getMapFor('Pomm\Test\Object\BaseEntity');
        static::$service = new BaseObjectMapModelLayer($connection);
        static::$service->createSchema();
    }

    public static function tearDownAfterClass()
    {
        static::$service->dropSchema();
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
     */
    public function testSave(BaseEntity $entity)
    {
        $entity['some data'] = 'plop';
        static::$map->saveOne($entity);

        $this->assertTrue((boolean) ($entity->_getStatus() & BaseObject::EXIST), "Object now exists in database.");
        $this->assertFalse($entity->isModified(), "Entity has not been modified since last persist operation.");
        $this->assertEquals(1, $entity['id'], "Entity has an ID.");
        $this->assertEquals('plop', $entity['some data'], "'some data' is unchanged.");
        $this->assertFalse($entity['bool_data'], "Bool data has been added.");

        $another_entity = static::$map->createAndSaveObject(Array('some data' => new \Pomm\Type\RawString("lower('MoRe pLoP')")));
        $this->assertTrue((boolean) ($another_entity->_getStatus() & BaseObject::EXIST), "Object now exists in database.");
        $this->assertEquals('more plop', $another_entity['some data'], "'some data' is unchanged.");

        $this->assertTrue($another_entity->has('ts_data'), "'ts_data' exists even when null.");
        $this->assertTrue(is_null($another_entity['ts_data']), "'ts_data' is null.");

        return $entity;
    }

    /**
     * @depends testSave
     */
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
     */
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
     */
    public function testFindWhere(BaseEntity $entity)
    {
        $test_entity = static::$map->findWhere('"some data" = $*', array($entity['some data']), 'ORDER BY id DESC LIMIT 1')->current();
        $this->assertNotSame($entity, $test_entity, "Entities are not the same instance 'check ident mapper'.");
        $this->assertEquals($entity['id'], $test_entity['id'], "Entities have the same id.");

        $test_entity = static::$map->findWhere(Where::create('"some data" = $*', array($entity['some data'])), null, 'ORDER BY id DESC LIMIT 1')->current();
        $this->assertNotSame($entity, $test_entity, "Entities are not the same instance 'check ident mapper'.");
        $this->assertEquals($entity['id'], $test_entity['id'], "Entities have the same id.");

        $test_entity = static::$map->findWhere('ts_data > $*', array(new \DateTime("2000-01-01")))->current();
        $this->assertEquals(1, $test_entity['id'], "Can feed findWhere with a DateTime instance.");


        return $entity;
    }


    /**
     * @depends testFindWhere
     */
    public function testFindByPK(BaseEntity $entity)
    {
        try
        {
            $test_entity = static::$map->findByPk(array('pika' => $entity['id']));
            $this->assertTrue(false, 'Not using PK definition throws a Pomm exception (no Exception caught).');
        }
        catch(\Pomm\Exception\Exception $e)
        {
            $this->assertTrue(true, 'Not using PK definition throws a Pomm exception.');
        }
        catch(\Exception $e)
        {
            $this->assertTrue(false, 'Not using PK definition throws a Pomm exception (\Exception caught).');
        }

        $test_entity = static::$map->findByPk(array('id' => $entity['id']));
        $this->assertTrue($test_entity == $entity, "Entities have the same values.");
        $this->assertNotSame($entity, $test_entity, "Entities are not the same instance.");

        return $entity;
    }

    /**
     * @depends testFindByPK
     */
    public function testQuery(BaseEntity $entity)
    {
        $sql = "SELECT %s FROM %s WHERE plop.id = $*";
        $sql = sprintf($sql,
            static::$map->formatFields('getSelectFields', 'plop'),
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
     */
    public function testUpdateByPk(BaseEntity $entity)
    {
        $dt = new \DateTime();
        try
        {
            static::$map->updateByPk(array('pika' => 1), array('ts_data' => $dt, 'bool_data' => false));
            $this->assertTrue(false, 'Not using PK definition throws a Pomm exception (no Exception caught).');
        }
        catch(\Pomm\Exception\Exception $e)
        {
            $this->assertTrue(true, 'Not using PK definition throws a Pomm exception.');
        }
        catch(\Exception $e)
        {
            $this->assertTrue(false, 'Not using PK definition throws a Pomm exception (\Exception caught).');
        }


        $entity = static::$map->updateByPk($entity->get(static::$map->getPrimaryKey()), array('ts_data' => $dt, 'bool_data' => false));

        $this->assertTrue(!$entity->isModified(), "Object is not marked as modified.");
        $this->assertTrue(!$entity['bool_data'], "Bool data is false.");
        $this->assertEquals($dt->format('U'), $entity['ts_data']->format('U'), "Timestamps are equals.");

        return $entity;
    }

    /**
     * @depends testUpdateByPk
     */
    public function testDelete(BaseEntity $entity)
    {
        static::$map->deleteOne($entity);
        $this->assertFalse((bool) ($entity->_getStatus() & BaseObject::EXIST), "After deletion, object is not marked as EXIST.");
        $this->assertTrue(is_null(static::$map->findByPk(array('id' => $entity['id']))), "Object does not exist in the DB anymore.");

        return $entity;
    }

    /**
     * @depends testDelete
     */
    public function testChangePrimaryKey(BaseEntity $entity)
    {
        static::$service->changeToMultiplePrimaryKey();
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
     */
    public function testFindAll(BaseEntity $entity)
    {
        static::$service->insertSomeData();
        $raw_res = static::$map->findAll();
        $ordered_res = static::$map->findAll('ORDER BY name ASC');
        $limited_res = static::$map->findAll('LIMIT 3');

        $this->assertEquals(5, $raw_res->count(), "5 results.");
        $this->assertEquals(5, $ordered_res->count(), "5 results.");

        foreach ($ordered_res as $index => $result)
        {
            $this->assertEquals( 5 - $index, $result['id'], "Names are the other way than ids.");
        }

        $this->assertEquals(3, $limited_res->count(), "We have 3 results.");
    }

    public function testCreateAndSaveObjects()
    {
        $collection = static::$map->createAndSaveObjects(array(
            array('name' => 'name1', 'some data' => 'one', 'bool_data' => true, 'ts_data' => new \DateTime()),
            array('name' => 'name2', 'some data' => 'two', 'bool_data' => true, 'ts_data' => null),
            array('name' => 'name3', 'some data' => 'three', 'bool_data' => false, 'ts_data' => null)
        ));

        $this->assertEquals(3, $collection->count(), 'The collection has 3 members');
        $first = $collection->current();

        $this->assertTrue($first->hasId(), "The first element has a primary key.");
        $this->assertTrue((BaseObject::EXIST & $first->_getStatus()) !== 0, "The first element exists in the database.");
        $this->assertFalse($first->isModified(), "The first element is not modified.");
    }

    public function testPager()
    {
        $pager = static::$map->paginateFindWhere('bool_data', array(), '', 10);

        $this->assertTrue(is_object($pager), 'Pager in an object');
        $this->assertInstanceOf('\Pomm\Object\Pager', $pager, 'Pager is a Pager instance.');
    }

    public function testFormatFields()
    {
        $field_list = static::$map->formatFields('getSelectFields');
        $this->assertEquals('"id", "some data", "bool_data", "ts_data", "name"', $field_list, "Field list is correctly formatted without field alias without table alias.");
        $field_list = static::$map->formatFields('getSelectFields', 'ts');
        $this->assertEquals('ts."id", ts."some data", ts."bool_data", ts."ts_data", ts."name"', $field_list, "Field list is correctly formatted without field alias with table alias.");
        $field_list = static::$map->formatFieldsWithAlias('getSelectFields');
        $this->assertEquals('"id" AS "id", "some data" AS "some data", "bool_data" AS "bool_data", "ts_data" AS "ts_data", "name" AS "name"', $field_list, "Field list is correctly formatted with field alias without table alias.");
        $field_list = static::$map->formatFieldsWithAlias('getSelectFields', 'ts');
        $this->assertEquals('ts."id" AS "id", ts."some data" AS "some data", ts."bool_data" AS "bool_data", ts."ts_data" AS "ts_data", ts."name" AS "name"', $field_list, "Field list is correctly formatted with field alias with table alias.");
        $field_list = static::$map->formatFields(static::$map->getSelectFields('ts'));
        $this->assertEquals('ts."id", ts."some data", ts."bool_data", ts."ts_data", ts."name"', $field_list, "Calling 'formatFields' with an array.");
        $field_list = static::$map->formatFieldsWithAlias(static::$map->getSelectFields('ts'));
        $this->assertEquals('ts."id" AS "id", ts."some data" AS "some data", ts."bool_data" AS "bool_data", ts."ts_data" AS "ts_data", ts."name" AS "name"', $field_list, "Calling 'formatFieldsWithAlias' with an array.");
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
        $sql = sprintf("create table %s (id serial primary key, \"some data\" varchar not null, bool_data boolean not null default false, ts_data timestamp)", $this->getTableName());
        $this->connection->executeAnonymousQuery($sql);
    }

    public function emptyTable()
    {
        $sql = sprintf("truncate table %s", $this->getTableName());
        $this->connection->executeAnonymousQuery($sql);

        return $this;
    }

    public function addColumn($name, $type)
    {
        $sql = sprintf("alter table %s add column %s %s not null", $this->getTableName(), $this->connection->escapeIdentifier($name), $type);;
        $this->connection->executeAnonymousQuery($sql);
        $this->addField($name, $type);

        return $this;
    }

    public function setPrimaryKey(Array $keys)
    {
        $sql = sprintf("alter table %s add primary key (%s)", $this->getTableName(), $this->formatFields($keys));
        $this->connection->executeAnonymousQuery($sql);
        $this->pk_fields = $keys;

        return $this;
    }

    public function removePrimaryKey()
    {
        $sql = sprintf("alter table %s drop constraint base_entity_pkey", $this->getTableName());;
        $this->connection->executeAnonymousQuery($sql);

        return $this;
    }

}

class BaseEntity extends BaseObject
{
}

class BaseObjectMapModelLayer extends ModelLayer
{
    protected function getBaseEntityMap()
    {
        return $this->connection->getMapFor('Pomm\Test\Object\BaseEntity');
    }

    public function createSchema()
    {
        try {
            $this->begin();
            $this->connection->executeAnonymousQuery( 'create schema pomm_test');
            $this->getBaseEntityMap()
                ->createTable();
            $this->commit();
        } catch (Exception $e) {
            $this->connection->rollback();
            throw $e;
        }

        return $this;
    }

    public function dropSchema()
    {
            $sql = "drop schema pomm_test cascade";
            $this->connection->executeAnonymousQuery($sql);

            return $this;
    }

    public function changeToMultiplePrimaryKey()
    {
        $this->begin();
            $this->getBaseEntityMap()
            ->emptyTable()
            ->removePrimaryKey()
            ->addColumn('name', 'varchar')
            ->setPrimaryKey(array('id', 'name'));
        $this->commit();

        return $this;
    }

    public function insertSomeData()
    {
        $this->connection->getMapFor('Pomm\Test\Object\BaseEntity')
            ->createAndSaveObjects(array(
                array('id' => 1, 'name' => 'echo', 'some data' => 'data', 'ts_data' => new \DateTime( '1975-06-29 21:15:43.123456')),
                array('id' => 4, 'name' => 'bravo', 'some data' => 'data', 'ts_data' => null),
                array('id' => 3, 'name' => 'charly', 'some data' => 'data', 'ts_data' => new \DateTime('1986-12-21 18:32:45.123456')),
                array('id' => 2, 'name' => 'dingo', 'some data' => 'data', 'ts_data' => new \DateTime('1993-06-29 02:45:33.123456')),
                array('id' => 5, 'name' => 'alpha', 'some data' => 'data', 'ts_data' => new \DateTime('2007-09-08 04:01:00.000000')),
            ));

        return $this;
    }
}
