<?php

namespace Pomm\Test\Connection;

use Pomm\Connection\Database;
use Pomm\Connection\Service;

class ObserverTest extends \PHPUnit_Framework_TestCase
{

    protected static $database;

    public static function setUpBeforeClass()
    {
        static::$database = new Database(array('dsn' => $GLOBALS['dsn'], 'name' => 'test_db'));
    }

    public function testNotify()
    {
        $service1 = new ObserverService(static::$database->createConnection());
        $service2 = new ObserverService(static::$database->createConnection());

        $observer = $service1->createObserver()
            ->listen("plop");

        for ($i = 0; $i < 10; $i++)
        {
            if ($i % 3 === 0)
            {
                $service2->notify('plop', "data $i");
            }
            else
            {
                $service2->notify('other event');
            }

            sleep(0.8);
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

        $service2->notify('plop');
        $data = $observer->getNotification();
        $this->assertEquals("", $data['payload'], 'When no payload, return an empty string.');
    }
}

class ObserverService extends Service
{
    public function createObserver()
    {
        return parent::createObserver();
    }

    public function notify($event, $payload)
    {
        return parent::notify($event, $payload);
    }
}
