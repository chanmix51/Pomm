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
        $this->assertEquals(47.21262, $entity['some_point']->x, "X coordinate is preserved.");
        $this->assertEquals(-1.55516, $entity['some_point']->y, "Y coordinate is preserved.");
        $this->assertTrue(is_array($entity['arr_point']), "'arr_point' is an array.");
        $this->assertEquals(2, count($entity['arr_point']), "Containing 2 elements.");
        $this->assertEquals(-33.969043, $entity['arr_point'][1]->x, "X of the 2nd element is preserved.");
        $this->assertEquals(151.187225, $entity['arr_point'][1]->y, "Y of the 2nd element is preserved.");

        $entity['arr_point'] = array(null, $entity['arr_point'][1], null, $entity['arr_point'][0], null);
        $entity['some_point'] = new Type\Point(0.12345E9, -9.87654E-9);

        static::$cv_map->updateOne($entity, array('arr_point', 'some_point'));

        $this->assertEquals(123450000, $entity['some_point']->x, "X coordinate is preserved.");
        $this->assertEquals(-9.87654E-9, $entity['some_point']->y, "Y coordinate is preserved.");
        $this->assertTrue(is_array($entity['arr_point']), "'arr_point' is an array.");
        $this->assertEquals(5, count($entity['arr_point']), "Containing 5 elements.");
        $this->assertTrue(is_null($entity['arr_point'][2]), '3rd element is null');
        $this->assertTrue(is_null($entity['arr_point'][4]), '5th element is null');
        $this->assertInstanceOf('\Pomm\Type\Point', $entity['arr_point'][1], "2nd element is a Point instance.");

        return $entity;
    }

    /**
     * @depends testPoint
     **/
    public function testCircle(ConverterEntity $entity)
    {
        static::$cv_map->alterCircle();
        $values = array(
            'some_circle' => new Type\Circle(new Type\Point(0.1234E9,-2), 10),
            'arr_circle' => array(new Type\Circle(new Type\Point(2,-2), 10), new Type\Circle(new Type\Point(0,0), 1))
        );

        $entity->hydrate($values);
        static::$cv_map->saveOne($entity);

        $this->assertInstanceOf('\Pomm\Type\Circle', $entity['some_circle'], "'some_circle' is a Circle type.");
        $this->assertInstanceOf('\Pomm\Type\Point', $entity['some_circle']->center, "'some_circle' center is a type Point.");
        $this->assertEquals(10, $entity['some_circle']->radius, "'some_circle' radius is preserved.");
        $this->assertTrue(is_array($entity['arr_circle']), "'arr_circle' is an array.");
        $this->assertEquals(2, count($entity['arr_circle']), "Containing 2 elements.");
        $this->assertInstanceOf('\Pomm\Type\Circle', $entity['arr_circle'][1], "'arr_circle' 2nd element is a Circle type.");
        $this->assertInstanceOf('\Pomm\Type\Point', $entity['arr_circle'][1]->center, "'arr_circle' 2nd element's center is a type Point.");
        $this->assertEquals(1, $entity['arr_circle'][1]->radius, "'arr_circle' 2nd element's radius is preserved.");

        $entity['arr_circle'] = array(null, $entity['arr_circle'][0], null,  $entity['arr_circle'][1], null);
        static::$cv_map->updateOne($entity, array('arr_circle'));

        $this->assertTrue(is_array($entity['arr_circle']), "'arr_circle' is an array.");
        $this->assertEquals(5, count($entity['arr_circle']), "Containing 5 elements.");
        $this->assertInstanceOf('\Pomm\Type\Circle', $entity['arr_circle'][1], "'arr_circle' 2nd element is a Circle type.");
        $this->assertTrue(is_null($entity['arr_circle'][2]), '3rd element is null');
        $this->assertTrue(is_null($entity['arr_circle'][4]), '5rd element is null');

        return $entity;
    }

    /**
     * @depends testCircle
     **/
    public function testSegment(ConverterEntity $entity)
    {
        static::$cv_map->alterSegment();
        $values = array(
            'some_lseg' => new Type\Segment(new Type\Point(0.1234E9,-2), new Type\Point(10, -10)),
            'arr_lseg' => array(new Type\Segment(new Type\Point(2,-2), new Type\Point(0,0)), new Type\Segment(new Type\Point(2,-2), new Type\Point(1000,-1000))),
        );

        $entity->hydrate($values);
        static::$cv_map->saveOne($entity);

        $this->assertInstanceOf('\Pomm\Type\Segment', $entity['some_lseg'], "'some_lseg' is a lseg type.");
        $this->assertInstanceOf('\Pomm\Type\Point', $entity['some_lseg']->point_a, "'some_lseg' point_a is a type Point.");
        $this->assertInstanceOf('\Pomm\Type\Point', $entity['some_lseg']->point_b, "'some_lseg' point_b is a type Point.");
        $this->assertTrue(is_array($entity['arr_lseg']), "'arr_lseg' is an array.");
        $this->assertEquals(2, count($entity['arr_lseg']), "Containing 2 elements.");
        $this->assertInstanceOf('\Pomm\Type\Segment', $entity['arr_lseg'][1], "'arr_lseg' 2nd element is a lseg type.");
        $this->assertInstanceOf('\Pomm\Type\Point', $entity['arr_lseg'][1]->point_b, "'arr_lseg' 2nd element's point_b is a type Point.");

        $entity['arr_lseg'] = array(null, $entity['arr_lseg'][0], null,  $entity['arr_lseg'][1], null);
        static::$cv_map->updateOne($entity, array('arr_lseg'));

        $this->assertTrue(is_array($entity['arr_lseg']), "'arr_lseg' is an array.");
        $this->assertEquals(5, count($entity['arr_lseg']), "Containing 5 elements.");
        $this->assertInstanceOf('\Pomm\Type\Segment', $entity['arr_lseg'][1], "'arr_lseg' 2nd element is a lseg type.");
        $this->assertTrue(is_null($entity['arr_lseg'][2]), '3rd element is null');
        $this->assertTrue(is_null($entity['arr_lseg'][4]), '5rd element is null');

        return $entity;
    }

    /**
     * @depends testSegment
     **/
    function testHStore(ConverterEntity $entity)
    {
        if (static::$cv_map->alterHStore() === false)
        {
            $this->markTestSkipped("HStore extension could not be found in Postgres, tests skipped.");

            return $entity;
        }

        $values = array('pika' => 'chu', 'plop' => null);
        $entity['some_hstore'] = $values;

        static::$cv_map->updateOne($entity, array('some_hstore'));
        $this->assertTrue(is_array($entity['some_hstore']), "'some_hstore' is an array.");
        $this->assertEquals($values, $entity['some_hstore'], "'some_hstore' array is preserved.");

        return $entity;
    }

    /**
     * @depends testSegment
     **/
    public function testLTree(ConverterEntity $entity)
    {
        if (static::$cv_map->alterLTree() === false)
        {
            $this->markTestSkipped("Ltree extension could not be found in Postgres, tests skipped.");

            return $entity;
        }

        $values = array(
            'some_ltree' => array('one', 'two', 'three', 'four'),
            'arr_ltree' => array(
                array('a', 'b', 'c', 'd'),
                array('a', 'b', 'e', 'f')
            ));

        $entity->hydrate($values);
        static::$cv_map->saveOne($entity);

        $this->assertTrue(is_array($entity['some_ltree']), "'some_ltree' is an array.");
        $this->assertEquals($values['some_ltree'], $entity['some_ltree'], "'some_ltree' is preserved.");
        $this->assertEquals($values['arr_ltree'][0], $entity['arr_ltree'][0], "'arr_ltree' 1st element is preserved.");

        $entity['arr_ltree'] = array(
            array(),
            null,
            $entity['arr_ltree'][0],
            array(),
            $entity['arr_ltree'][1],
            null);

        static::$cv_map->updateOne($entity, array('arr_ltree'));

        $this->assertEquals(array(), $entity['arr_ltree'][0], "Empty ltrees are preserved.");
        $this->assertTrue(is_null($entity['arr_ltree'][1]), "Nulls are preserved.");
        $this->assertTrue(is_array($entity['arr_ltree'][2]), "3rd element is an array.");
        $this->assertEquals($values['arr_ltree'][0], $entity['arr_ltree'][2], "Ltree are preserved.");
        $this->assertTrue(is_null($entity['arr_ltree'][5]), "5th element is null");

        return $entity;
    }

    /**
     * @depends testSegment
     **/
    public function testTsRange(ConverterEntity $entity)
    {
        if (static::$cv_map->alterTsRange() === false)
        {
            $this->markTestSkipped("tsrange type could not be found, maybe Postgres version < 9.2. Tests skipped.");

            return $entity;
        }

        $value = new Type\TsRange(new \DateTime('2012-08-20'), new \DateTime('2012-09-01'), true);
        $entity['some_tsrange'] = $value;

        static::$cv_map->updateOne($entity, array('some_tsrange'));

        $this->assertInstanceOf('\Pomm\Type\TsRange', $entity['some_tsrange'], "'some_tsrange' is a 'TsRange' type.");
        $this->assertEquals($value->start->format('U'), $entity['some_tsrange']->start->format('U'), "Timestamps are equal.");

        $entity['arr_tsrange'] = array($value, new Type\TsRange(new \DateTime('2012-12-21'), new \DateTime('2012-12-21 12:21:59')));

        static::$cv_map->updateOne($entity, array('some_tsrange', 'arr_tsrange'));

        $this->assertEquals(2, count($entity['arr_tsrange']), "There are 2 elements in the array.");
        $this->assertInstanceOf('\Pomm\Type\TsRange', $entity['arr_tsrange'][1], "'arr_tsrange' element 1 is a 'TsRange' type.");
        $this->assertEquals(44519, $entity['arr_tsrange'][1]->end->format('U') - $entity['arr_tsrange'][1]->start->format('U'), "Range is preserved.");

        return $entity;
    }

    /**
     * @depends testSegment
     **/
    public function testSuperConverter(ConverterEntity $entity)
    {
        $super_entity = new SuperConverterEntity(array('cv_entities' => array($entity)));

        static::$super_cv_map->saveOne($super_entity);

        $this->assertTrue(is_int($super_entity['id']), "'id' has been generated.");
        $this->assertTrue(is_array($super_entity['cv_entities']), "'cv_entities' is an array.");
        $this->assertInstanceOf('Pomm\Test\Converter\ConverterEntity', $super_entity['cv_entities'][0], "'cv_entities' is a ConverterEntity instance.");
        $this->assertEquals(1, $super_entity['cv_entities'][0]->get('id'), "'id' is 1.");
        $this->assertEquals(array(1.0, 1.1, null, 1.3), $super_entity['cv_entities'][0]->get('arr_fl'), "'arr_fl' of 'cv_entities' is preserved.");

        $new_entity = new ConverterEntity(array('some_point' => new Type\Point(12,-3)));
        $super_entity->add('cv_entities', $new_entity);
        static::$super_cv_map->updateOne($super_entity, array('cv_entities'));

        $this->assertInstanceOf('\Pomm\Type\Point', $super_entity['cv_entities'][1]['some_point'], "'some_point' of 2nd element is a Point.");
        $this->assertEquals(12, $super_entity['cv_entities'][1]['some_point']->x, "With x = 12");
        $this->assertEquals(-3, $super_entity['cv_entities'][1]['some_point']->y, "With y = -3");
        $this->assertTrue(is_null($super_entity['cv_entities'][1]['some_bin']), "'some_bin' is null.");
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

    protected function checkType($type)
    {
        $sql = sprintf("SELECT pg_namespace.nspname FROM pg_type JOIN pg_namespace ON pg_type.typnamespace = pg_namespace.oid WHERE typname = '%s'", $type);

        return $this->connection
            ->executeAnonymousQuery($sql)
            ->fetchColumn(0);
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
        if ($schema = $this->checkType('hstore') === false)
        {
            return false;
        }

        $this->alterTable(array('some_hstore' => 'hstore'));

        $this->connection->getDatabase()
            ->registerConverter('HStore', new Converter\PgHStore(), array('hstore', 'public.hstore', $schema.'.hstore'));
    }

    public function alterLTree()
    {
        if ($schema = $this->checkType('ltree') === false)
        {
            return false;
        }

        $this->alterTable(array('some_ltree' => 'ltree', 'arr_ltree' => 'ltree[]'));

        $this->connection->getDatabase()
            ->registerConverter('LTree', new Converter\PgLTree(), array('ltree', 'public.ltree', $schema.'.ltree'));
    }

    public function alterTsRange()
    {
        if ($schema = $this->checkType('tsrange') === false)
        {
            return false;
        }

        $this->alterTable(array('some_tsrange' => 'tsrange', 'arr_tsrange' => 'tsrange[]'));

        $this->connection->getDatabase()
            ->registerConverter('tsrange', new Converter\PgTsRange(), array('tsrange', 'public.tsrange', $schema.'.tsrange'));
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
