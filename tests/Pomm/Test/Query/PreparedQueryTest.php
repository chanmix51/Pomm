<?php

namespace Pomm\Test\Query;

use Pomm\Connection\Database;
use \Pomm\Exception\Exception as PommException;

class PreparedQueryTest extends \PHPUnit_Framework_TestCase
{
    protected static $connection;

    public static function setUpBeforeClass()
    {
        $database = new Database(array('dsn' => $GLOBALS['dsn'], 'name' => 'test_db'));
        static::$connection = $database->getConnection();
    }

    public function testExecute()
    {
        $query = static::$connection->createPreparedQuery("SELECT i FROM generate_series(1, 10) i WHERE i % $* = 0");

        $this->assertEquals('a697dce597da49654e23bc8a883fd069', $query->getName(), "Name is the md5 of the sql");
        $this->assertTrue($query->getActive(), 'Query is active');

        $res2 = $query->execute(array(2));
        $res3 = $query->execute(array(3));
        $res4 = $query->execute(array(4));

        $this->assertEquals(5, pg_num_rows($res2), 'Res2 has 5 results.');
        $this->assertEquals(3, pg_num_rows($res3), 'Res3 has 3 results.');
        $this->assertEquals(2, pg_num_rows($res4), 'Res4 has 2 results.');
    }

    public function testDeallocate()
    {
        $query = static::$connection->createPreparedQuery("SELECT false");
        $this->assertTrue($query->getActive(), 'Query is active');
        $query->deallocate();
        $this->assertFalse($query->getActive(), 'Query is active');

        try
        {
            $query->execute(array());
            $this->assertFalse(true, 'Query should throw an exception when inactive');
        }
        catch(PommException $e)
        {
            $this->assertTrue(true, "When inactive, an executed query throws an Exception.");
        }
    }
}
