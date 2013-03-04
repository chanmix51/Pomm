<?php

namespace Pomm\Test\Connection;

use Pomm\Connection\Database;

class DatabaseTest extends \PHPUnit_Framework_TestCase
{
    public function getDsns()
    {
        return array(
            array('pgsql://user:pass@host:12345/db'),
            array('pgsql://user@host:12345/db'),
            array('pgsql://user@!/var/run/postgresql!/db'),
            array('pgsql://user@!/var/run/postgresql!:12345/db'),
            array('pgsql://user@192.168.0.1/db'),
            array('pgsql://user:pass@telenet.host.com:9999/db'),
            array('pgsql://user/db'),
            array('pgsql://user/some.db'),
            array('pgsql://user:&~"#\'{([-|`_\\^])+=}$*%!/:.;?,@host:12345/db'),
            array('pgsql://some_user:azerty0/some_db'),
            array('pgsql://user:pass@ec2-34-143-188-54.compute-N.amazonawz.com:5432/db_name')
        );
    }

    /**
     * @dataProvider getDsns
     **/
    public function testProcessDsn($dsn)
    {
        $database = new Database(array('dsn' => $dsn));
        $this->assertInstanceOf('\Pomm\Connection\Database', $database, "Database is an instance.");
    }
}
