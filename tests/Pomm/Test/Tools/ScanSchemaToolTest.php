<?php

namespace Pomm\Test\Tools;

use Pomm\Connection\Database;
use Pomm\Exception\Exception;
use Pomm\Tools\ScanSchemaTool;

class ScanSchemaToolTest extends \PHPUnit_Framework_TestCase
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

            $sql = 'CREATE TABLE pomm_test.pika1 (pika1_id int4)';
            static::$connection->executeAnonymousQuery($sql);

            $sql = 'CREATE TABLE pomm_test.pika2 (pika2_id int4)';
            static::$connection->executeAnonymousQuery($sql);

            $sql = 'CREATE TABLE pomm_test.pika3 (pika3_id int4)';
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

    public function testScanSchema()
    {
        $tool = new ScanSchemaTool(array(
            'prefix_dir' => static::$tmp_dir,
            'database' => static::$connection->getDatabase(),
            'schema' => 'pomm_test',
            'exclude' => array('pika2')
        ));

        $tool->execute();

        $path = static::$tmp_dir.DIRECTORY_SEPARATOR.'TestDb'.DIRECTORY_SEPARATOR.'PommTest';
        $this->assertFileExists($path.DIRECTORY_SEPARATOR.'Pika1.php');
        $this->assertFileExists($path.DIRECTORY_SEPARATOR.'Pika1Map.php');
        $this->assertFileNotExists($path.DIRECTORY_SEPARATOR.'Pika2.php');
        $this->assertFileNotExists($path.DIRECTORY_SEPARATOR.'Pika2Map.php');
        $this->assertFileExists($path.DIRECTORY_SEPARATOR.'Pika3.php');
        $this->assertFileExists($path.DIRECTORY_SEPARATOR.'Pika3Map.php');
        $this->assertFileExists($path.DIRECTORY_SEPARATOR.'Base'.DIRECTORY_SEPARATOR.'Pika1Map.php');
        $this->assertFileNotExists($path.DIRECTORY_SEPARATOR.'Base'.DIRECTORY_SEPARATOR.'Pika2Map.php');
        $this->assertFileExists($path.DIRECTORY_SEPARATOR.'Base'.DIRECTORY_SEPARATOR.'Pika3Map.php');
    }
}
