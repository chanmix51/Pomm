<?php

namespace Pomm\Test\Tools;

use Pomm\Connection\Database;
use Pomm\Connection\ModelLayer;
use Pomm\Exception\Exception;
use Pomm\Tools\ScanSchemaTool;

class ScanSchemaToolTest extends \PHPUnit_Framework_TestCase
{
    protected static $connection;
    protected static $tmp_dir;
    protected static $service;

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
        static::$service = new ScanSchemaToolModelLayer(static::$connection);
        static::$service->createSchema();
    }

    public static function tearDownAfterClass()
    {
        static::$service->dropSchema();
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

    public function testScanAll()
    {
        $tool = new ScanSchemaTool(array(
            'prefix_dir' => static::$tmp_dir,
            'database' => static::$connection->getDatabase(),
            'schema' => 'pomm_test'
        ));

        $tool->execute();

        $path = static::$tmp_dir.DIRECTORY_SEPARATOR.'TestDb'.DIRECTORY_SEPARATOR.'PommTest';
        $this->assertFileExists($path.DIRECTORY_SEPARATOR.'Pika2.php');
        $this->assertFileExists($path.DIRECTORY_SEPARATOR.'Pika2Map.php');
        $this->assertFileExists($path.DIRECTORY_SEPARATOR.'Base'.DIRECTORY_SEPARATOR.'Pika2Map.php');
    }
}

class ScanSchemaToolModelLayer extends ModelLayer
{
    public function createSchema()
    {
        $this->begin();
        try
        {
            $sql = 'create schema pomm_test';
            $this->connection->executeAnonymousQuery($sql);

            $sql = 'create table pomm_test.pika1 (pika1_id int4)';
            $this->connection->executeAnonymousQuery($sql);

            $sql = 'create table pomm_test.pika2 (pika2_id int4)';
            $this->connection->executeAnonymousQuery($sql);

            $sql = 'create table pomm_test.pika3 (pika3_id int4)';
            $this->connection->executeAnonymousQuery($sql);

            $this->commit();
        }
        catch (ConnectionException $e)
        {
            $this->rollback();

            throw $e;
        }
    }

    public function dropSchema()
    {
        $sql = 'drop schema pomm_test cascade';
        $this->connection->executeAnonymousQuery($sql);
    }
}
