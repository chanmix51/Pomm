<?php

namespace Pomm\Test\Tools;

use Pomm\Connection\Database;
use Pomm\Exception\Exception;
use Pomm\Tools\CreateBaseMapTool;

class CreateBaseMapToolTest extends \PHPUnit_Framework_TestCase
{
    protected static $connection;
    protected static $tmp_dir;

    public static function setUpBeforeClass()
    {
        static::$tmp_dir = isset($GLOBALS['tmp_dir']) ? $GLOBALS['tmp_dir'] : sys_get_temp_dir();

        if (!is_dir(static::$tmp_dir))
        {
            throw new Exception(sprintf("Directory '%s' does not exist.", static::$tmp_dir));
        }

        if (!is_writeable(static::$tmp_dir))
        {
            throw new Exception(sprintf("Directory '%s' is not writeable.", static::$tmp_dir));
        }
        $database = new Database(array('dsn' => $GLOBALS['dsn'], 'name' => 'test_db'));

        static::$connection = $database->createConnection();
        static::$connection->begin();

        try
        {
            $sql = 'CREATE SCHEMA pomm_test';
            static::$connection->executeAnonymousQuery($sql);

            $sql = 'CREATE TABLE pomm_test.pika (id serial PRIMARY KEY, some_char char(10), some_varchar varchar)';
            static::$connection->executeAnonymousQuery($sql);

            $sql = 'CREATE TYPE pomm_test.some_type AS (ts timestamp, md5 char(32))';
            static::$connection->executeAnonymousQuery($sql);

            $sql = 'CREATE TABLE pomm_test.chu (some_some_type pomm_test.some_type) INHERITS (pomm_test.pika)';
            static::$connection->executeAnonymousQuery($sql);

            $sql = 'ALTER TABLE pomm_test.pika ADD COLUMN fixed_arr numeric(4,3)[]';
            static::$connection->executeAnonymousQuery($sql);

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

        exec(sprintf('rm -r %s', static::$tmp_dir.DIRECTORY_SEPARATOR.'TestDb'));
    }

    protected function checkFiles($table, $class, $filenames, $other_options = array())
    {
        $options = array(
            'table' => $table,
            'schema' => 'pomm_test',
            'database' => static::$connection->getDatabase(),
            'prefix_dir' => static::$tmp_dir,
        );

        $options = array_merge($options, $other_options);

        $tool = new CreateBaseMapTool($options);
        $tool->execute();

        $fixture_dir = realpath(__DIR__.'/../../Fixture');

        foreach ($filenames as $filename) {
            $f = str_replace(static::$tmp_dir, $fixture_dir, $filename);
            $f = str_replace('/', DIRECTORY_SEPARATOR, $f);
            $filename = str_replace('/', DIRECTORY_SEPARATOR, $filename);
            $this->assertFileEquals($f, $filename);
        }
    }

    public function testGenerateBaseMap()
    {
        $root_dir = static::$tmp_dir.'/TestDb/PommTest';

        $this->checkFiles('pika', 'Pika', array(
            $root_dir.'/Base/PikaMap.php',
            $root_dir.'/Pika.php',
            $root_dir.'/PikaMap.php',
        ));
    }

    public function testGenerateBaseMapInherits()
    {
        $root_dir = static::$tmp_dir.'/TestDb/PommTestProd';

        $this->checkFiles('chu', 'Chu', array(
            $root_dir.'/Base/ChuMap.php',
            $root_dir.'/Chu.php',
            $root_dir.'/ChuMap.php',
        ), array(
            'namespace' => '%dbname%\%schema%Prod',
            'parent_namespace' => '\%dbname%\%schema%',
        ));
    }
}
