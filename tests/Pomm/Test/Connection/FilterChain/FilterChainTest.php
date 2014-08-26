<?php

namespace Pomm\Test\Connection\FilterChain;

use Pomm\Connection\Database;
use Pomm\Exception\Exception;
use Pomm\Connection\FilterChain\FilterInterface;
use Pomm\Connection\FilterChain\QueryFilterChain;

class FilterChainTest extends \PHPUnit_Framework_TestCase
{
    protected static $connection;

    public static function setUpBeforeClass()
    {
        $database = new Database(array('dsn' => $GLOBALS['dsn'], 'name' => 'test_db'));
        static::$connection = $database->createConnection();
    }

    public function testRegisterFilter()
    {
        $filter_chain = static::$connection->filter_chain
            ->registerFilter(new TestFilter());

        $filter_definition = $filter_chain->dumpDefinition();

        $this->assertEquals('Pomm\Test\Connection\FilterChain\TestFilter', $filter_definition[0], 'The filter is correctly registered.');
        static::$connection->query('SELECT $*::int', array(1));
    }

    public function testInsertFilter()
    {
        $filter_chain = static::$connection->filter_chain
            ->insertFilter(new TestFilter(), 1);

        $filter_definition = $filter_chain->dumpDefinition();

        $this->assertEquals('Pomm\Test\Connection\FilterChain\TestFilter', $filter_definition[1], 'The filter is correctly inserted.');

        try
        {
            $filter_chain->insertFilter(new TestFilter(), 10);
            $this->fail('Inserting a filter out of filters index must throw an InvalidArgumentException (none caught).');
        }
        catch (\InvalidArgumentException $e)
        {
            $this->assertTrue(true, 'Inserting a filter out of filters index must throw an InvalidArgumentException.');
        }
        catch(\Exception $e)
        {
            $this->fail(sprintf("Inserting a filter out of filters index must throw an InvalidArgumentException ('%s' caught).", get_class($e)));
        }
    }

    public function testReplaceFilter()
    {
        $filter_chain = static::$connection->filter_chain
            ->replaceFilter(new AlternateTestFilter(), 1);

        $filter_definition = $filter_chain->dumpDefinition();

        $this->assertEquals('Pomm\Test\Connection\FilterChain\AlternateTestFilter', $filter_definition[1], 'The filter is correctly replaced.');

        try
        {
            $filter_chain->replaceFilter(new TestFilter(), 10);
            $this->fail('Replacing a non existent filter must throw an InvalidArgumentException (none caught).');
        }
        catch (\InvalidArgumentException $e)
        {
            $this->assertTrue(true, 'Replacing a non existent filter must throw an InvalidArgumentException (none caught).');
        }
        catch(\Exception $e)
        {
            $this->fail(sprintf("Replacing a non existent filter must throw an InvalidArgumentException ('%s' caught).", get_class($e)));
        }
    }
}

class TestFilter extends \PHPUnit_Framework_TestCase implements FilterInterface
{
    public function execute(QueryFilterChain $filter_chain)
    {
        $this->assertTrue(true, 'Entering test filter');
        $ret = $filter_chain->executeNext($filter_chain);
        $this->assertTrue(is_resource($ret), 'Query filter chain returns a resource.');
        $this->assertEquals('pgsql result', get_resource_type($ret), 'The resource type is a pgsql result.');

        return $ret;
    }
}

class AlternateTestFilter extends TestFilter
{
}
