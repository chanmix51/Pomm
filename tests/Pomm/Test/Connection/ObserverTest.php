<?php

namespace Pomm\Test\Connection;

use Pomm\Connection\Database;

class ObserverTest extends \PHPUnit_Framework_TestCase
{

    protected static $database;

    public static function setUpBeforeClass()
    {
        static::$database = new Database(array('dsn' => $GLOBALS['dsn'], 'name' => 'test_db'));
    }

    public function testNotify()
    {
        $connection1 = static::$database->createConnection();
        $connection2 = static::$database->createConnection();

        $observer = $connection1->createObserver()
            ->listen("plop");

        for ($i = 0; $i < 10; $i++)
        {
            if ($i % 3 === 0)
            {
                $connection2->notify('plop', "data $i");
            }
            else
            {
                $connection2->notify('other event');
            }

            sleep(0.3);
            $data = $observer->getNotification();

            if ($i % 3 === 0)
            {
                $this->assertTrue(is_array($data), "Iteration '$i', returned data is an array.");
                $this->assertEquals("data $i", $data['payload'], "Iteration '$i', the payload is correct.");
            }
            else
            {
                $this->assertFalse($data);
            }
        }

        $connection2->notify('plop');
        $data = $observer->getNotification();
        $this->assertEquals("", $data['payload'], 'When no payload, return an empty string.');
    }
}

