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

        static::$tmp_dir = isset($GLOBALS['tmp_dir']) ? $GLOBALS['tmp_dir'] : DIRECTORY_SEPARATOR.'tmp';

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

            $sql = 'CREATE TABLE pomm_test.pika (id serial PRIMARY KEY, some_char char(10), some_varchar varchar, fixed_arr numeric(4,3)[])';
            static::$connection->executeAnonymousQuery($sql);

            $sql = 'CREATE TYPE pomm_test.some_type AS (ts timestamp, md5 char(32))';
            static::$connection->executeAnonymousQuery($sql);

            $sql = 'CREATE TABLE pomm_test.chu (some_some_type pomm_test.some_type) INHERITS (pomm_test.pika)';
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

        exec(sprintf("rm -r %s", static::$tmp_dir.DIRECTORY_SEPARATOR."TestDb"));
    }

    protected function checkFiles($table, $class, $md5sums)
    {
        $tool = new CreateBaseMapTool(array(
            'table' => $table,
            'schema' => 'pomm_test',
            'database' => static::$connection->getDatabase(),
            'prefix_dir' => static::$tmp_dir,
        ));

        $tool->execute();

        foreach ($md5sums as $hash => $file)
        {

            $this->assertFileExists($file, sprintf("file '%s' does exist.", $file));
            $this->assertEquals($hash, md5(file_get_contents($file)), sprintf("Content hash match for file '%s'.", $file));
        }
    }

    public function testGenerateBaseMap()
    {
        $path = sprintf("%s%s%s%s%s", static::$tmp_dir,
            DIRECTORY_SEPARATOR,
            'TestDb',
            DIRECTORY_SEPARATOR,
            'PommTest');

        $this->checkFiles('pika', 'Pika', array(
            "af8594137516d9ff83d30d728fbf0404" => sprintf("%s%sBase%s%s", $path, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, 'PikaMap.php'),
            "801c71fac28e0ae40d22fb3f61c208d6" => sprintf("%s%s%s", $path, DIRECTORY_SEPARATOR, 'Pika.php'),
            "80dd30f55c806c327785890e31002027" => sprintf("%s%s%s", $path, DIRECTORY_SEPARATOR, 'PikaMap.php')));
    }

    public function testGenerateBaseMapInherits()
    {
        $path = sprintf("%s%s%s%s%s", static::$tmp_dir,
            DIRECTORY_SEPARATOR,
            'TestDb',
            DIRECTORY_SEPARATOR,
            'PommTest');

        $this->checkFiles('chu', 'Chu', array(
            "33e75141ca8657df30f12737b13cf3be" => sprintf("%s%sBase%s%s", $path, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, 'ChuMap.php'),
            "0fea710e0a4646d1e5be1582c4ad6e80" => sprintf("%s%s%s", $path, DIRECTORY_SEPARATOR, 'Chu.php'),
            "7b46a6cbeac24f71578bb756933e7dd1" => sprintf("%s%s%s", $path, DIRECTORY_SEPARATOR, 'ChuMap.php')));
    }
}
