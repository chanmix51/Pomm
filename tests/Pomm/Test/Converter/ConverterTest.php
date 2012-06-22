<?php

namespace Pomm\Test\Converter;

use Pomm\Connection\Database;
use Pomm\Object\BaseObject;
use Pomm\Object\BaseObjectMap;
use Pomm\Exception\Exception;
use Pomm\Converter;
use Pomm\Type;

class ConverterTest extends \PHPUnit_Framework_TestCase
{
    protected static $cv_map;
    protected static $super_cv_map;
    protected static $logger;
    protected static $connection;

    public static function setUpBeforeClass()
    {
        $database = new Database(array('dsn' => $GLOBALS['dsn'], 'name' => 'test_db'));

        static::$connection = $database->createConnection();

        if (isset($GLOBALS['dev']) && $GLOBALS['dev'] == 'true') 
        {
            static::$logger = new \Pomm\Tools\Logger();

            static::$connection->registerFilter(new \Pomm\FilterChain\LoggerFilter(static::$logger));
        } 

        static::$connection->begin();
        try
        {
            $sql = 'CREATE SCHEMA pomm_test';
            static::$connection->executeAnonymousQuery($sql);

            static::$cv_map = static::$connection->getMapFor('Pomm\Test\Converter\ConverterEntity');
            static::$cv_map->createTable();

            static::$super_cv_map = static::$connection->getMapFor('Pomm\Test\Converter\SuperConverterEntity');
            static::$super_cv_map->createTable(static::$cv_map);
            static::$connection->commit();
        }
        catch (Exception $e)
        {
            static::$connection->rollback();

            throw $e;
        }
    }

    public static function tearDownAfterClass()
    {
        $sql = 'DROP SCHEMA pomm_test CASCADE';
        static::$connection->executeAnonymousQuery($sql);

        !is_null(static::$logger) && print_r(static::$logger);
    }

    public function testInteger()
    {
        $entity = static::$cv_map->createObject(array('id' => 1, 'fixed' => 12.345, 'fl' => 0.000001, 'arr_fl' => array(1.0,1.1,1.2,1.3)));
        static::$cv_map->saveOne($entity);

        $this->assertEquals(1, $entity['id'], "PHP int 1 <=> PG int 1");
        $this->assertEquals(12.345, $entity['fixed'], "PHP 12.345 <=> PG 12.345 numeric");
        $this->assertEquals(0.000001, $entity['fl'], "PHP 0.000001 <=> PG 1e-6");
        $this->assertEquals(array(1.0, 1.1, 1.2, 1.3), $entity['arr_fl'], "Float array is preserved.");

        $entity['fixed'] = 0.0001;
        $entity['fl'] = 1000000.000001;
        $entity['arr_fl'] = array(1.0, 1.1, null, 1.3);
        static::$cv_map->saveOne($entity);

        $this->assertEquals(0.000, $entity['fixed'], "PHP 0.0001 <=> PG 0.000 numeric");
        $this->assertEquals(1000000, $entity['fl'], "PHP 1000000.000001 <=> PG 1e+6");
        $this->assertEquals(array(1.0, 1.1, null, 1.3), $entity['arr_fl'], "Float array preserves null.");

        return $entity;
    }

    /**
     * @depends testInteger
     **/
    public function testString(ConverterEntity $entity)
    {
        static::$cv_map->alterText();
        $values = array('some_char' => 'abcdefghij', 'some_varchar' => '1234567890 abcdefghij', 'some_text' => 'Lorem Ipsum', 'arr_varchar' => array('pika', 'chu'));

        $entity->hydrate($values);
        static::$cv_map->updateOne($entity, array_keys($values));

        $this->assertEquals('abcdefghij', $entity['some_char'], "Chars are ok.");
        $this->assertEquals('1234567890 abcdefghij', $entity['some_varchar'], "Varchars are ok.");
        $this->assertEquals('Lorem Ipsum', $entity['some_text'], "Text is ok.");
        $this->assertEquals(array('pika', 'chu'), $entity['arr_varchar'], "Varchar arrays are ok.");

        $entity['some_char'] = 'a        b';
        $entity['some_varchar'] = '&"\'-- =+_-;\\?,{}[]()';
        $entity['some_text'] = '';
        $entity['arr_varchar'] = array(null, '123', null, '', null, 'abc');
        static::$cv_map->updateOne($entity, array_keys($values));

        $this->assertEquals('a        b', $entity['some_char'], "Chars' length is kept.");
        $this->assertEquals('&"\'-- =+_-;\?,{}[]()', $entity['some_varchar'], "Non alpha is escaped.");
        $this->assertEquals('', $entity['some_text'], "Empty strings are ok.");
        $this->assertEquals(array(null, '123', null, '', null, 'abc'), $entity['arr_varchar'], "Char arrays can contain nulls and emtpy strings.");

        return $entity;
    }

    /**
     * @depends testString
     **/
    public function testDate(ConverterEntity $entity)
    {
        static::$cv_map->alterDate();
        $values = array('some_ts' => '2012-06-20 18:34:16.640044', 'some_intv' => '30 days', 'arr_ts' => array('2015-06-08 03:54:08.880287', '1994-12-16 21:23:50.224208', '1941-02-18 17:29:52.216309'));

        $entity->hydrate($values);
        static::$cv_map->saveOne($entity);

        $this->assertInstanceOf('\DateTime', $entity['some_ts'], "'some_ts' is a \DateTime instance.");
        $this->assertEquals( '2012-06-20 18:34:16.640044', $entity['some_ts']->format('Y-m-d H:i:s.u'), "Timestamp is preserved.");
        $this->assertInstanceOf('\DateInterval', $entity['some_intv'], "'some_intv' is a \DateInterval instance.");
        $this->assertEquals('30', $entity['some_intv']->format('%d'), "'some_intv' has 30 days.");
        $this->assertEquals(3, count($entity['arr_ts']), "'arr_ts' is an array of 3 elements.");
        $this->assertInstanceOf('\DateTime', $entity['arr_ts'][2], "Third element of 'arr_ts' is a DateTime instance.");
        $this->assertEquals('1941-02-18 17:29:52.216309', $entity['arr_ts'][2]->format('Y-m-d H:i:s.u'), "Array timestamp is preserved.");

        $entity['arr_ts'] = array('2015-06-08 03:54:08.880287', null,  '1941-02-18 17:29:52.216309');
        static::$cv_map->updateOne($entity, array('arr_ts'));

        $this->assertEquals(3, count($entity['arr_ts']), "'arr_ts' is an array of 3 elements.");
        $this->assertTrue(is_null($entity['arr_ts'][1]), "Second element of 'arr_ts' is null.");

        return $entity;
    }

    /**
     * @depends testDate
     **/
    public function testBool(ConverterEntity $entity)
    {
        static::$cv_map->alterBool();
        $values = array('some_bool' => true, 'arr_bool' => array(true, false, true));

        $entity->hydrate($values);
        static::$cv_map->saveOne($entity);

        $this->assertTrue($entity['some_bool'], "'some_bool' is boolean and TRUE.");
        $this->assertEquals(3, count($entity['arr_bool']), "'arr_bool' is an array of 3 elements.");
        $this->assertFalse($entity['arr_bool'][1], "Second element of 'arr_bool' is FALSE.");

        $entity['arr_bool'] = array(true, false, null, false, null, null);

        static::$cv_map->updateOne($entity, array('arr_bool'));

        $this->assertEquals(6, count($entity['arr_bool']), "'arr_bool' is 6 elements array.");
        $this->assertTrue(is_null($entity['arr_bool'][2]), "3th element is NULL.");
        $this->assertFalse($entity['arr_bool'][3], "4th element is FALSE.");
        $this->assertTrue(is_null($entity['arr_bool'][4]), "5th element is NULL.");
        $this->assertTrue(is_null($entity['arr_bool'][5]), "6th element is NULL.");

        return $entity;
    }

    protected function checksumBinary($binary)
    {
        return md5($binary);
    }

    /**
     * @depends testBool
     *
     * Tests are volontarily simplistic, read below:
     *
     * pg bytea escaping with PHP :
     * https://bugs.php.net/bug.php?id=59831&thanks=6
     *
     * I don't know why PHP seems to strip some chars from bytea when fetching 
     * results on some large objects wich makes fail tests (See issue #31).
     *
     * Arrays of bytea is not supported as PHP returns this field as string and 
     * it (or myself) was not able to convert strings to binary (See issue #32).
     *
     **/
    public function testBinary(ConverterEntity $entity)
    {
        static::$cv_map->alterBinary();
        $binary = chr(0).chr(27).chr(92).chr(39).chr(32).chr(13);
        $hash = $this->checksumBinary($binary);
        $length = strlen($binary);

        $values = array('some_bin' => $binary);
        $entity->hydrate($values);

        static::$cv_map->saveOne($entity);

        $this->assertEquals($length, strlen($entity['some_bin']), "Binary strings have same length.");
        $this->assertEquals($hash, $this->checksumBinary($entity['some_bin']), "Small 'some_bin' is preserved.");


        return $entity;
    }

    /**
     * @depends testBinary
     **/
    public function testPoint(ConverterEntity $entity)
    {
        static::$cv_map->alterPoint();
        $values = array(
            'some_point' => new Type\Point(47.21262, -1.55516), 
            'arr_point' => array(new Type\Point(6.431264, 3.424915), new Type\Point(-33.969043, 151.187225))
        );

        $entity->hydrate($values);
        static::$cv_map->saveOne($entity);

        $this->assertInstanceOf('\Pomm\Type\Point', $entity['some_point'], "'some_point' is a Point instance.");
        $this->assertEquals(47.21262, (float) $entity['some_point']->x, "X coordinate is preserved.");
        $this->assertEquals(-1.55516, (float) $entity['some_point']->y, "Y coordinate is preserved.");
        $this->assertTrue(is_array($entity['arr_point']), "'arr_point' is an array.");
        $this->assertEquals(2, count($entity['arr_point']), "Containing 2 elements.");
        $this->assertEquals(-33.969043, $entity['arr_point'][1]->x, "X of the 2nd element is preserved.");
        $this->assertEquals(151.187225, $entity['arr_point'][1]->y, "Y of the 2nd element is preserved.");

        $entity['arr_point'] = array(null, $entity['arr_point'][1], null, $entity['arr_point'][0], null);

        static::$cv_map->updateOne($entity, array('arr_point'));

        $this->assertTrue(is_array($entity['arr_point']), "'arr_point' is an array.");
        $this->assertEquals(5, count($entity['arr_point']), "Containing 5 elements.");
        $this->assertTrue(is_null($entity['arr_point'][2]), '3rd element is null');
        $this->assertTrue(is_null($entity['arr_point'][4]), '5th element is null');
        $this->assertInstanceOf('\Pomm\Type\Point', $entity['arr_point'][1], "2nd element is a Point instance.");

        return $entity;
    }
}

class ConverterEntityMap extends BaseObjectMap
{
    protected function initialize()
    {
        $this->object_class = '\Pomm\Test\Converter\ConverterEntity';
        $this->object_name  = 'pomm_test.cv_entity';

        $this->addField('id', 'int4');
        $this->addField('fixed', 'numeric');
        $this->addField('fl', 'float4');
        $this->addField('arr_fl', 'float4[]');

        $this->pk_fields = array('id');
    }

    public function createTable()
    {
        $sql = sprintf("CREATE TABLE %s (id serial PRIMARY KEY, fixed numeric(5,3), fl float4, arr_fl float4[])", $this->getTableName());
        $this->connection->executeAnonymousQuery($sql);
    }

    protected function alterTable(Array $fields)
    {
        $this->connection->begin();
        try
        {
            foreach($fields as $field => $type)
            {
                $sql = sprintf("ALTER TABLE %s ADD COLUMN %s %s", $this->getTableName(), $field, $type);
                $this->connection->executeAnonymousQuery($sql);
                $this->addField($field, strtok($type, '('));
            }

            $this->connection->commit();
        }
        catch(Exception $e)
        {
            $this->connection->rollback();

            throw $e;
        }
    }

    public function alterText()
    {
        $this->alterTable(array('some_char' => 'char(10)', 'some_varchar' => 'varchar', 'some_text' => 'text', 'arr_varchar' => 'varchar[]'));
    }

    public function alterDate()
    {
        $this->alterTable(array('some_ts' => 'timestamp', 'some_intv' => 'interval', 'arr_ts' => 'timestamp[]'));
    }

    public function alterBool()
    {
        $this->alterTable(array('some_bool' => 'bool', 'arr_bool' => 'bool[]'));
    }

    public function alterBinary()
    {
        $this->alterTable(array('some_bin' => 'bytea'));
    }

    public function alterPoint()
    {
        $this->alterTable(array('some_point' => 'point', 'arr_point' => 'point[]'));

        $this->connection->getDatabase()
            ->registerConverter('Point', new Converter\PgPoint(), array('point'));
    }

    public function alterCircle()
    {
        $this->alterTable(array('some_circle' => 'circle', 'arr_circle' => 'circle[]'));

        $this->connection->getDatabase()
            ->registerConverter('Circle', new Converter\PgCircle(), array('circle'));
    }

    public function alterSegment()
    {
        $this->alterTable(array('some_lseg' => 'lseg', 'arr_lseg' => 'lseg[]'));

        $this->connection->getDatabase()
            ->registerConverter('Segment', new Converter\PgLseg(), array('lseg'));
    }

    public function alterHStore()
    {
        $this->alterTable(array('some_hstore' => 'hstore'));

        $this->connection->getDatabase()
            ->registerConverter('HStore', new Converter\PgHStore(), array('hstore', 'public.hstore'));
    }

    public function alterLTree()
    {
        $this->alterTable(array('some_ltree' => 'ltree', 'arr_ltree' => 'ltree[]'));

        $this->connection->getDatabase()
            ->registerConverter('LTree', new Converter\PgLTree(), array('ltree', 'public.ltree'));
    }
}

class ConverterEntity extends BaseObject
{
}

class SuperConverterEntityMap extends BaseObjectMap
{
    protected function initialize()
    {
        $this->object_class = '\Pomm\Test\Converter\SuperConverterEntity';
        $this->object_name  = 'pomm_test.super_cv_entity';

        $this->addField('id', 'int4');
        $this->addField('cv_entities', 'pomm_test.cv_entity[]');

        $this->pk_fields = array('id');
    }

    public function createTable(ConverterEntityMap $map)
    {
        $sql = sprintf('CREATE TABLE %s (id serial PRIMARY KEY, cv_entities pomm_test.cv_entity[])', $this->getTableName());
        $this->connection
            ->executeAnonymousQuery($sql);

        $this->connection
            ->getDatabase()
            ->registerConverter('ConverterEntity', new Converter\PgEntity($map), array('pomm_test.cv_entity'));
    }
}

class SuperConverterEntity extends BaseObject
{
}
