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
        $entity = static::$cv_map->createObject(array('id' => 1, 'fixed' => 12.345, 'fl' => 0.000001, 'arr_fl' => array(1.0,1.1,1.2,1.3), 'not_null_string' => ''));
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
    }

    /**
     * @depends testInteger
     **/
    public function testString()
    {
        static::$cv_map->alterText();
        $entity = static::$cv_map->findAll()->current();
        $values = array('some_char' => 'abcdefghij', 'some_varchar' => '1234567890 abcdefghij', 'some_text' => 'Lorem Ipsum', 'arr_varchar' => array('pika', '{"a","b b \'c"}'));

        $entity->hydrate($values);
        static::$cv_map->updateOne($entity, array_keys($values));

        $this->assertEquals('abcdefghij', $entity['some_char'], "Chars are ok.");
        $this->assertEquals('1234567890 abcdefghij', $entity['some_varchar'], "Varchars are ok.");
        $this->assertEquals('Lorem Ipsum', $entity['some_text'], "Text is ok.");
        $this->assertEquals(array('pika', '{"a","b b \'c"}'), $entity['arr_varchar'], "Varchar arrays are ok.");

        $entity['some_char'] = 'a        b';
        $entity['some_varchar'] = '&"\'-- =+_-;\\?,{}[]()';
        $entity['some_text'] = '';
        $entity['arr_varchar'] = array(null, '123', null, '', null, 'abc');
        static::$cv_map->updateOne($entity, array_keys($values));

        $this->assertEquals('a        b', $entity['some_char'], "Chars' length is kept.");
        $this->assertEquals('&"\'-- =+_-;\?,{}[]()', $entity['some_varchar'], "Non alpha is escaped.");
        $this->assertEquals('', $entity['some_text'], "Empty strings are ok.");
        $this->assertEquals(array(null, '123', null, '', null, 'abc'), $entity['arr_varchar'], "Char arrays can contain nulls and emtpy strings.");

        if (static::$cv_map->alterJson() !== false)
        {
            $entity['some_json'] = json_encode(array('plop' => array('pika' => 'chu', 'lot of' => array(1, 2, 3, 4, 5))));
            static::$cv_map->updateOne($entity, array('some_json'));

            $this->assertEquals('{"plop":{"pika":"chu","lot of":[1,2,3,4,5]}}', $entity['some_json'], "Json type is kept unchanged.");
        }
        else
        {
            $this->markTestSkipped("Json type is supported since postgresql 9.2. Test skipped.");
        }

        $entity['some_char'] = null;
        static::$cv_map->saveOne($entity);

        $this->assertTrue(is_null($entity['some_char']), "Strings NULL are preserved.");

        return $entity;
    }

    /**
     * @depends testInteger
     **/
    public function testDate()
    {
        static::$cv_map->alterDate();
        $entity = static::$cv_map->findAll()->current();
        $values = array(
            'some_ts' => '2012-06-20 18:34:16.640044+10',
            'some_intv' => '37 years 3 months 7 days 2 hours 14 minutes 46 seconds',
            'arr_ts' => array('2015-06-08 03:54:08.880287', '1994-12-16 21:23:50.224208', '1941-02-18 17:29:52.216309')
        );

        $entity->hydrate($values);
        static::$cv_map->saveOne($entity);

        $this->assertInstanceOf('\DateTime', $entity['some_ts'], "'some_ts' is a \DateTime instance.");
        $entity['some_ts']->setTimeZone(new \DateTimeZone('Etc/GMT0'));
        $this->assertEquals( '2012-06-20 08:34:16.640044+00:00', $entity['some_ts']->format('Y-m-d H:i:s.uP'), "Timestamp is preserved.");
        $this->assertInstanceOf('\DateInterval', $entity['some_intv'], "'some_intv' is a \DateInterval instance.");
        $this->assertEquals('37 years 3 mons 7 days 02:14:46', $entity['some_intv']->format("%y years %m mons %d days %H:%i:%s"), "'some_intv' is '37 years 3 mons 7 days 02:14:46'.");
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
     * @depends testInteger
     **/
    public function testBool()
    {
        static::$cv_map->alterBool();
        $entity = static::$cv_map->findAll()->current();
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

    /**
     * @depends testInteger
     **/
    public function testBinary()
    {
        static::$cv_map->alterBinary();
        $entity = static::$cv_map->findAll()->current();
        $binary = chr(0).chr(27).chr(92).chr(39).chr(32).chr(13);
        $base64 = <<<_
iVBORw0KGgoAAAANSUhEUgAAACEAAAAyCAYAAADSprJaAAAABGdBTUEAALGPC/xhBQAAAAFzUkdCAK7OHOkAAAAgY0hSTQAAeiYAAICEAAD6AAAAgOgAAHUwAADqYAAAOpgAABdwnLpRPAAAAAZiS0dEAAAAAAAA+UO7fwAAAAlwSFlzAAALEwAACxMBAJqcGAAAAAl2cEFnAAAAIQAAADIANFI/6wAACp5JREFUWMO9mHdwVXUWxz+/W957SV7qeyG9IiEFSJAIKEg3gIguFnRFQBEriwMP1KG4oIACC+ugoK6FcS3jxrLjwOqwAuqooI5YRwOIRCGFEiAQSM/9nf3jvUCwjIrZnJk7t5xf+dxzzu/7u3PVqKTooddmnyz2HNAyswCOF8Dw92F0kUluTBhr3m/lmzubCS+P5NS6Nq71NrLnALxVB9FFd2IkJEjdM4sdc9rzJ5LLnj3sr9xyaMc6KpOX0lx9gBP8RpvjAbl6DMIyJCkV2bMd8buRWJDwNUooQlLnWsIM5JGLkCwLiRx/r5A2QNSFmUIF4oktku6jb9RWWM8G5rM//jq+OHbYmlX52aAhv0rQvYcnEB3wytUrvTLERMZfbsvji5RERbjFyFPC7Uh8XJjYdythCFI2ALGIFOPKNRLjQihEuAKd4TU0/gzNyPt0pA+9t7abPDQlToCDN11h/+vBBfEXe9JjzJ+nSCdAuCnPZCCFXiTWNvSeLw25cSoCCEsQXkWMMCXJINvGBZ/nXTpd1PgNwrDzNUVJonKmi8q+UFREmn5mmaGXPZamoypitO1WOiUM2f1GeAMwG4j5CUOUNyKQ7jLl6T7BwTN8SP1RdFZ0CAKXABIW4ZJ8DzIhEYkMs8QGsbP6inHbfLGGLxf6XSeABvT2/3r0mMGm7u736MQlbkncGK6T3zAcXkGYyEtAwtkUIwjsvhWZCvL+eiQwySXT8iwNHGTgP19mzmelhn9QqTKjSodEU7qzhNKRLlV6RwKl30+htOdzvP5DT7a+1pvdGdmZAsjRGpycKCW+MDR4dPTAEr2zHE3sYO0CQbGmG0SdZoh9jkDvecjB9wx5Yk6yGCgNCPn3fP1bKxtg6T1FqY0TzBErruKhfZ+4KiG8jcGLHEqW6vDxs6SqAkkYcoNkT3mkzczs1WKOW3j76c6PTCDgnEC6rUCIQUwQEvKEv7xZBoT9HpB2e++O6HzfjH9vjUnJF8DxWMiG17qJC3R2SqzGcothez4G8gA4vo/AFVkIRQjzEB5EuHm0MLVPGUPPDQKgJd1KX9qLF/P83iZAb96Enn6NVyxcmlQ0kxHzKW4GMK4dC5uqLPgCWAlssiDrU0j76ufq+Deba3/b/rj8jNvfLdLfRwHffAnTZzdJ24gWhQ9FNEQ/qu7a9GpiDL4IAjGxtpREIX+7ABmSM0r6XTBDlqZQNvkc09HRvr6OMZtzg6vmrlVZkv5+ilaZhgac+Fiqn3/OTjM2+mCpu5XiNKirg+3dB+I+8hLDimHt6D+KAAWD2VEOOwD1ZFo1t33ZqOJOaOKAgYV+f0q4L5u1A2MD8RF2SBMQpj8rKEuAcy7MH5vXw1yiED5ET7wG2bsrQZYsRF5/DZGmrMnGi9W11NS3Eh/pgt5j4eVFuGzBUJ0xfdBONfEdBTg0oF5+BRk9vAbLmysNezPIz6lO5uFFBFb+GQFbmN8qECbjByN+H2Xr13VOJIBh4d3UqR6TPZIU721XVkkAebUvS6n8NjEg4pKnXz5PuGGdNB9CRLySnWGURXqNzoI4n3hOshZBBdO+wELkJqRqMsuNAUWHmHaJyf2bbiH64yeZOdemd65Qvk9z8pTuFIJpk/BRg4ELcCHTTCQ8AeZuh5znaYFMAlyGMNYWMhGsIKnH7SobVJTbKZH4/FMmxkQiPIAmEpF4ZGqeEdweYJbBw0Av4LI2eARYBMRAU3ML277Y1SmRSEmlMCkesEOHQr7ZqRWAggMGE4BDj8LqcXA5JDo27pkG3Nop89PcRI7j2Nfv3we4gHB4pwZ1iQsZDU1PGfxgkFAAcVdB+RsAHFzcSkFNBN3HeWHeH4eYcg2DpkyOzKx3EBwULphsIxcVwAVw+HvND0BkgHah6nBk9Y8vm1bb4w/VxJrV2b0vGhh+CHAAh8UIPYMS3htkCWxdCFEwdkOA4kU/gQDKFOY5Q8gNDPDDh6GxHI9l6KgRhkR0twWFng7OfXANgGH5v0JdVgiewmDvObvg/mOQXILgyDnMH2HB5O2NrN+RzEBAF/d0G1os3E1KfD6XvnJoqrIMY4sD7wHgi0+eExV4S3j4pBi5QwV/sUT86XFhwf5deJMKCX545JFDHgvIo4C8Jshb0v6c/r2ATLZKPAsrHySr5CPgeIqNyDycO7rRvhSFqWjG0ga0xsCYdmrraE218PcSCBuCbtwGONS/vkM4/5gPaZkXGkDhRiiEGzeCOxyeFxSNCJfe4XF/ndqrdX7gvMweCZSPeg2e9klVawv91mL0iYIPbGR2K9i+CHbuqhcTFh6BtzuG76zCjI02gtqe0F+HdtMzR7EpxxSyPrX9mVeY85Io68x+wM3rNLnzT98nK7S+DkeKkcOKtj0WK3orXD/O4VkQUV63gKGJv0xQtvijPdo0lJgg20Dq0hHTHWrvuli45MP2cGtAkzpI6Hl9+70T8rXdd5X56afzoibKrVg/qaK93xLYvw+5dtxpEE3EBcLQFwTDdRruaxDpjwwbjTAy1NY9Qhj+ggD6StCzQN8Ozk0KPddGrGDfzcADpJH0S5VsJCYmcLzOpq4VFt8NgCLsbo3aJugW4ggKXfagHlzVnMi7BtA91Lv5Y8g7HxeoAmiOhNoE2JkuvB3ZysrhMBqYBPyVCg78EoSVlnGMY7VtfPYJ9C2G5Wv703T5KMX6id8CN88C5xOg5HADH+w5CCl0+ACuhy1P6NugzoBwDVqgXEOzA22bg+n4TRYAZMwY5M4ZYULCG0LqpJBY4T4HnTgnCwCiUMKwZcKM+wQ69xvz18xoP0neJCjy02/biyjacAH5XRQFA2IhYg2MLSFn8wqee6Ec0wrmoW8XQVi+PuNJLLApf30xW94u58gBMCRYUTUW0NYFEJ5je/lhw5s01h+hoQnqG6HVgRbgnS4AALCqKrcBYCpw2XCyIagwAK1dw9BemBAXB9HRsLusi2buGAnbDIa/sRmWLPCx8e3j/A6N6TSbnZWMXDEAcZmIbRviTwjvUp0ACJQ+i4gg6elndlNXF4uVKyULKvdB3fEzjoFdFQLACoOWuFg4dBjqTp1xvBc65+fnqRMnTgSAvoZhHNdaP1ZVVV2WmpoyExARyQNqgc+VUldqrT+rrj6wOjk5aaJhGLEich7gdxxnpmmaqw3DaAAeqKiorD0dicJciImBxibwa1gOLOtAWVdXN14ptQr4XGs9AXgp5BoDrAK0UmqBUuoJYK9pmqt8vjhbKXWxUuoJpVSdUmqUaZrHgBpgloiMOisdI/1ge0C1wXbgXuDiyLN+TvQBjlZVVa8Gxiul+hAMgQ38p6qqemao3TgRWS8ieDyeIaEo7a6srFpC8E9NQ1VV9UKtNUD0WenABaYBjgXkQm00/KPRgK+Cy1RETiqlfKH2FxLSMqUUQFP7QCJiKqXcAKZpOo7jABwMuU+/Vajf2TWxW0AE3C6Y2QgffQe1bWd0wjCMN0VkQUpK8pEQzOJ2V8fBASUiSilF6G0VZ8Twx23PhjhUi3JaICkOth2BujP7hQKoqKjck5KSXASkAvWVlVXfhN78FkLbm4j0A3YppRwRKVRKfQd8KyLhIf9d7ddAkYjs7wihkmKYtP0TRiQmoXNyoaIy6DDhVBjMO9Uh5P8v+x8Pgos+1qR8KAAAABl0RVh0Q29tbWVudABDcmVhdGVkIHdpdGggR0lNUFeBDhcAAAAldEVYdGRhdGU6Y3JlYXRlADIwMTItMDMtMDlUMTY6MDQ6MjcrMDA6MDCZh7kaAAAAJXRFWHRkYXRlOm1vZGlmeQAyMDEyLTAzLTA5VDE2OjA0OjI3KzAwOjAw6NoBpgAAABd0RVh0cG5nOmJpdC1kZXB0aC13cml0dGVuAAinxCzyAAAAAElFTkSuQmCC
_;

        $values = array('some_bin' => $binary, 'arr_bin' => array($binary, base64_decode($base64)));
        $entity->hydrate($values);

        static::$cv_map->saveOne($entity);

        $this->assertEquals(strlen($binary), strlen($entity['some_bin']), "Binary strings have same length.");
        $this->assertEquals(base64_encode($binary), base64_encode($entity['some_bin']), "Small 'some_bin' is preserved.");

        $this->assertEquals(2, count($entity['arr_bin']), "The array has 2 elements.");
        $this->assertEquals($binary, $entity['arr_bin'][0], "First value in the array is preserved.");
        $this->assertEquals($base64, base64_encode($entity['arr_bin'][1]), "Second value in the array is preserved.");

        $entity['some_bin'] = base64_decode($base64);
        static::$cv_map->updateOne($entity, array('some_bin'));

        $this->assertEquals($base64, base64_encode($entity['some_bin']), "Image is preserved.");

        return $entity;
    }

    /**
     * @depends testInteger
     **/
    public function testPoint()
    {
        static::$cv_map->alterPoint();
        $entity = static::$cv_map->findAll()->current();
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
     * @depends testInteger
     **/
    public function testCircle()
    {
        static::$cv_map->alterCircle();
        $entity = static::$cv_map->findAll()->current();
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
     * @depends testInteger
     **/
    public function testSegment()
    {
        static::$cv_map->alterSegment();
        $entity = static::$cv_map->findAll()->current();
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
    }

    /**
     * @depends testInteger
     **/
    function testHStore()
    {
        if (static::$cv_map->alterHStore() === false)
        {
            $this->markTestSkipped("HStore extension could not be found in Postgres, tests skipped.");

            return;
        }

        $entity = static::$cv_map->findAll()->current();
        $values = array('pika' => 'chu', 'plop' => null, 'grum' => '', 'quote' => '"', '"a"=>"b,a"' => '"c" => "d,a"' );
        $entity['some_hstore'] = $values;

        static::$cv_map->updateOne($entity, array('some_hstore'));
        $this->assertTrue(is_array($entity['some_hstore']), "'some_hstore' is an array.");
        $this->assertEquals($values, $entity['some_hstore'], "'some_hstore' array is preserved.");
    }

    /**
     * @depends testInteger
     **/
    public function testLTree()
    {
        if (static::$cv_map->alterLTree() === false)
        {
            $this->markTestSkipped("Ltree extension could not be found in Postgres, tests skipped.");

            return;
        }

        $entity = static::$cv_map->findAll()->current();
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
    }

    /**
     * @depends testInteger
     **/
    public function testTsRange()
    {
        if (static::$cv_map->alterTsRange() === false)
        {
            $this->markTestSkipped("tsrange type could not be found, maybe Postgres version < 9.2. Tests skipped.");

            return;
        }

        $entity = static::$cv_map->findAll()->current();
        $value = new Type\TsRange(new \DateTime('2012-08-20'), new \DateTime('2012-09-01'), array(
            'end' => Type\Range::END_EXCL,
        ));
        $entity['some_tsrange'] = $value;

        static::$cv_map->updateOne($entity, array('some_tsrange'));

        $this->assertInstanceOf('\Pomm\Type\TsRange', $entity['some_tsrange'], "'some_tsrange' is a 'TsRange' type.");
        $this->assertEquals($value->start->format('U'), $entity['some_tsrange']->start->format('U'), "Timestamps are equal.");

        $entity['arr_tsrange'] = array($value, new Type\TsRange(new \DateTime('2012-12-21'), new \DateTime('2012-12-21 12:21:59'), array(
            'start' => Type\Range::START_EXCL,
            'end'   => Type\Range::END_EXCL,
        )));

        static::$cv_map->updateOne($entity, array('some_tsrange', 'arr_tsrange'));

        $this->assertEquals(2, count($entity['arr_tsrange']), "There are 2 elements in the array.");
        $this->assertInstanceOf('\Pomm\Type\TsRange', $entity['arr_tsrange'][1], "'arr_tsrange' element 1 is a 'TsRange' type.");
        $this->assertEquals(44519, $entity['arr_tsrange'][1]->end->format('U') - $entity['arr_tsrange'][1]->start->format('U'), "Range is preserved.");

        return $entity;
    }

    /**
     * @depends testInteger
     **/
    public function testNumberRangeConverter()
    {
        if (static::$cv_map->alterNumberRange() === false)
        {
            $this->markTestSkipped("Range types could not be found, maybe Postgres version < 9.2. Tests skipped.");

            return;
        }

        $entity = static::$cv_map->findAll()->current();

        $entity['some_int4range'] = new Type\NumberRange(-5, 45, array(
            'end' => Type\Range::END_EXCL,
        ));
        $entity['some_int8range'] = new Type\NumberRange(4452940833, 4553946490, array(
            'start' => Type\Range::START_EXCL,
            'end'   => Type\Range::END_EXCL,
        ));
        $entity['some_numrange']  = new Type\NumberRange(29.76607095, 30.44125206, array(
            'start' => Type\Range::START_EXCL,
            'end'   => Type\Range::END_EXCL,
        ));
        $entity['arr_numrange']   = array(
            new Type\NumberRange(1.1, 1.2),
            new Type\NumberRange(2.2, 2.4, array(
                'end' => Type\Range::END_EXCL,
            )),
            new Type\NumberRange(3.3, 3.6, array(
                'start' => Type\Range::START_EXCL,
                'end'   => Type\Range::END_EXCL,
            )
        ));

        static::$cv_map->updateOne($entity, array('some_int8range', 'some_int4range', 'some_numrange', 'arr_numrange'));

        $this->assertEquals(new Type\NumberRange(-5, 45, array(
            'end' => Type\Range::END_EXCL,
        )), $entity['some_int4range'], "Int4range is ok.");
        $this->assertEquals(new Type\NumberRange(4452940834, 4553946490, array(
            'end' => Type\Range::END_EXCL,
        )), $entity->getSomeInt8range(), "Int8range is ok.");
        $this->assertEquals(new Type\NumberRange(29.76607095, 30.44125206, array(
            'start' => Type\Range::START_EXCL,
            'end'   => Type\Range::END_EXCL,
        )), $entity['some_numrange'], "Numrange is ok.");
        $this->assertTrue(is_array($entity['arr_numrange']), "'arr_numrange' is an array.");

        for($x = 1; $x <= count($entity['arr_numrange']); $x++)
        {
            $range = $entity['arr_numrange'][$x - 1];
            $this->assertInstanceOf('\Pomm\Type\NumberRange', $range, "Instance 'NumberRange'.");
            $this->assertEquals((float) $x * 1.1, $range->start, "Range start is ok.");
            $this->assertEquals((float) $x * 1.2, $range->end, "Range en is ok.");
        }
    }


    /**
     * @depends testInteger
     */
    public function testRowConverter()
    {
        static::$cv_map->alterComposite();

        $entity = static::$cv_map->findAll()->current();

        $test_address = new AddressType(array('place' => '11, impasse juton', 'postal_code' => '44000', 'city' => 'Nantes'));
        $entity['a_composite'] = $test_address;
        $entity['arr_composite'] = array($test_address, new AddressType(array('place' => 'Middle of nowhere', 'postal_code' => '56590', 'city' => 'Groix')));
        static::$cv_map->updateOne($entity, array('a_composite', 'arr_composite'));

        $this->assertTrue($entity['a_composite'] instanceOf \Pomm\Test\Converter\AddressType, "Composite data is an object.");
        $this->assertEquals((array) $test_address, (array) $entity['a_composite'], "Row is unchanged.");
        $this->assertEquals((array) $test_address, (array) $entity['arr_composite'][0], "Array of rows is unchanged.");
    }

    /**
     * @depends testInteger
     **/
    public function testSuperConverter()
    {
        $entity = static::$cv_map->findAll()->current();
        $super_entity = new SuperConverterEntity(array('cv_entities' => array($entity)));

        static::$super_cv_map->saveOne($super_entity);

        $this->assertTrue(is_int($super_entity['id']), "'id' has been generated.");
        $this->assertTrue(is_array($super_entity['cv_entities']), "'cv_entities' is an array.");
        $this->assertInstanceOf('Pomm\Test\Converter\ConverterEntity', $super_entity['cv_entities'][0], "'cv_entities' is an array with first value as ConverterEntity instance.");
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

    /**
     * @depends testInteger
     **/
    public function testTsExtendedEntity()
    {
        $ts_entity_map = static::$connection
            ->getMapFor('\Pomm\Test\Converter\TsEntity');
        $ts_entity_map->createTable();

        $ts_extended_entity_map = static::$connection
            ->getMapFor('\Pomm\Test\Converter\TsExtendedEntity');
        $ts_extended_entity_map->createTable($ts_entity_map);

        $collection = $ts_extended_entity_map->findAll();
        $ts_extended_entity = $collection->current();

        $this->assertInstanceOf('\Pomm\Test\Converter\TsEntity', $ts_extended_entity['p1'], "'p1' is a TsEntity instance.");
        $this->assertEquals('2000-02-29', $ts_extended_entity['p1']['p1']->format('Y-m-d'), 'Timestamp is preserved.');

        foreach (array('1999-12-31 23:59:59.999999', '2005-01-29 23:01:58.000000') AS $key => $ts)
        {
            $this->assertEquals($ts, $ts_extended_entity['p2'][$key]['p1']->format('Y-m-d H:i:s.u'), 'Timestamp array is preserved.');
        }

        $ts_over_extended_entity_map = static::$connection
            ->getMapFor('\Pomm\Test\Converter\TsOverExtendedEntity');
        $ts_over_extended_entity_map->createTable($ts_extended_entity_map);

        $collection = $ts_over_extended_entity_map->findAll();
        $ts_over_extended_entity = $collection->current();

        $this->assertInstanceOf('\Pomm\Test\Converter\TsExtendedEntity', $ts_over_extended_entity['p1'], "'p1' is a TsExtendedEntity instance.");
        $this->assertTrue(is_array($ts_over_extended_entity['p2']), "'p2' is an Array.");
        $this->assertInstanceOf('\Pomm\Test\Converter\TsExtendedEntity', $ts_over_extended_entity['p2'][0], "Which contains TsExtendedEntity instances.");
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
        $this->addField('not_null_string', 'text');

        $this->pk_fields = array('id');
    }

    public function createTable()
    {
        $sql = sprintf("CREATE TABLE %s (id serial PRIMARY KEY, fixed numeric(5,3), fl float4, arr_fl float4[], not_null_string text NOT NULL)", $this->getTableName());
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

    public function checkType($type)
    {
        $sql = sprintf("SELECT pg_namespace.nspname FROM pg_type JOIN pg_namespace ON pg_type.typnamespace = pg_namespace.oid WHERE typname = '%s'", $type);

        $result = pg_fetch_assoc($this->connection
            ->executeAnonymousQuery($sql));

        if ($result === false)
        {
            return false;
        }

        return $result['nspname'];
    }

    public function alterText()
    {
        $this->alterTable(array('some_char' => 'char(10)', 'some_varchar' => 'varchar', 'some_text' => 'text', 'arr_varchar' => 'varchar[]'));
    }

    public function alterDate()
    {
        $this->alterTable(array('some_ts' => 'timestamptz', 'some_intv' => 'interval', 'arr_ts' => 'timestamp[]'));
    }

    public function alterBool()
    {
        $this->alterTable(array('some_bool' => 'bool', 'arr_bool' => 'bool[]'));
    }

    public function alterBinary()
    {
        $this->alterTable(array('some_bin' => 'bytea', 'arr_bin' => 'bytea[]'));
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

    public function alterJson()
    {
        if ($schema = $this->checkType('json') === false)
        {
            return false;
        }

        $this->alterTable(array('some_json' => 'json'));
    }

    public function alterNumberRange()
    {
        if ($schema = $this->checkType('numrange') === false)
        {
            return false;
        }

        $this->alterTable(array('some_int4range' => 'int4range', 'some_int8range' => 'int8range', 'some_numrange' => 'numrange', 'arr_numrange' => 'numrange[]'));
    }

    public function alterComposite()
    {
        $this->connection->executeAnonymousQuery("CREATE TYPE pomm_test.address AS (place TEXT, postal_code CHAR(5), city varchar)");
        $this->alterTable(array('a_composite' => 'pomm_test.address', 'arr_composite' => 'pomm_test.address[]'));

        $this->connection->getDatabase()
            ->registerConverter(
                'Address',
                new Converter\PgRow(
                    $this->connection->getDatabase(),
                    new \Pomm\Object\RowStructure(array('place' => 'text', 'postal_code' => 'char', 'city' => 'varchar')),
                    '\Pomm\Test\Converter\AddressType'
                ),
                array('pomm_test.address', 'address'));
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

class TsEntityMap extends BaseObjectMap
{
    protected function initialize()
    {
        $this->object_class = '\Pomm\Test\Converter\TsEntity';
        $this->object_name = "( VALUES ('1999-12-31 23:59:59.999999'::timestamp) ) AS ts_entity (p1)";

        $this->addField('p1', 'timestamp');

        $this->pk_fields = array();
    }

    public function createTable()
    {
        $sql = 'CREATE TYPE pomm_test.ts_entity AS (p1 timestamp)';
        $this->connection
            ->executeAnonymousQuery($sql);
    }
}

class TsEntity extends BaseObject
{
}

class TsExtendedEntityMap extends BaseObjectMap
{
    protected function initialize()
    {
        $this->object_class = '\Pomm\Test\Converter\TsExtendedEntity';
        $this->object_name  = <<<SQL
( VALUES
  ( ROW('2000-02-29'::timestamp)::pomm_test.ts_entity, ARRAY[ROW('1999-12-31 23:59:59.999999'::timestamp)::pomm_test.ts_entity, ROW('2005-01-29 23:01:58'::timestamp)::pomm_test.ts_entity]::pomm_test.ts_entity[] ),
  ( ROW('2004-02-29'::timestamp)::pomm_test.ts_entity, ARRAY[ROW('1989-12-31 23:59:59.999999'::timestamp)::pomm_test.ts_entity, ROW('2005-01-29 23:01:58'::timestamp)::pomm_test.ts_entity]::pomm_test.ts_entity[] )
) ts_extended_entity (p1, p2)
SQL;

        $this->addField('p1', 'pomm_test.ts_entity');
        $this->addField('p2', 'pomm_test.ts_entity[]');

        $this->pk_fields = array();
    }

    public function createTable(TsEntityMap $map)
    {
        $sql = 'CREATE TYPE pomm_test.ts_extended_entity AS (p1 pomm_test.ts_entity, p2 pomm_test.ts_entity[])';
        $this->connection
            ->executeAnonymousQuery($sql);

        $this->connection
            ->getDatabase()
            ->registerConverter('TsEntityConverter', new Converter\PgEntity($map), array('pomm_test.ts_entity', 'ts_entity'));
    }
}

class TsExtendedEntity extends BaseObject
{
}

class TsOverExtendedEntityMap extends BaseObjectMap
{
    protected function initialize()
    {
        $this->object_class = '\Pomm\Test\Converter\TsOverExtendedEntity';
        $this->object_name  = <<<SQL
( VALUES
  ( ROW(ROW('2000-02-29'::timestamp)::pomm_test.ts_entity, ARRAY[ROW('1999-12-31 23:59:59.999999'::timestamp)::pomm_test.ts_entity, ROW('2005-01-29 23:01:58'::timestamp)::pomm_test.ts_entity]::pomm_test.ts_entity[] )::pomm_test.ts_extended_entity, ARRAY[ROW(ROW('2004-02-29'::timestamp)::pomm_test.ts_entity, ARRAY[ROW('1989-12-31 23:59:59.999999'::timestamp)::pomm_test.ts_entity, ROW('2005-01-29 23:01:58'::timestamp)::pomm_test.ts_entity]::pomm_test.ts_entity[])::pomm_test.ts_extended_entity]::pomm_test.ts_extended_entity[] )
) ts_over_extended_entity (p1, p2)
SQL;

        $this->addField('p1', 'pomm_test.ts_extended_entity');
        $this->addField('p2', 'pomm_test.ts_extended_entity[]');

        $this->pk_fields = array();
    }

    public function createTable(TsExtendedEntityMap $map)
    {
        $sql = 'CREATE TYPE pomm_test.ts_over_extended_entity AS (p1 pomm_test.ts_extended_entity, p2 pomm_test.ts_extended_entity[])';
        $this->connection
            ->executeAnonymousQuery($sql);

        $this->connection
            ->getDatabase()
            ->registerConverter('TsExtendedEntityConverter', new Converter\PgEntity($map), array('pomm_test.ts_extended_entity', 'pomm_test.extended_entity'));
    }
}

class TsOverExtendedEntity extends BaseObject
{
}

class AddressType extends \Pomm\Type\Composite
{
    public $place;
    public $postal_code;
    public $city;
    public $cedex;

    protected $something = 'pika';
}
