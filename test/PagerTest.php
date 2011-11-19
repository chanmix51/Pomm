<?php

include __DIR__.'/../Pomm/External/lime.php';
include "autoload.php";
include "PommBench.php";

use Pomm\Service;
use Pomm\Connection\Database;
use Pomm\Query\Where;

class PagerTest extends \lime_test
{
    protected $service;
    protected $map;

    public function initialize()
    {
        $this->service = new Service();
        $this->service->setDatabase('default', new Database(array('dsn' => 'pgsql://greg/greg')));
        $this->map = $this->service->createConnection()->getMapFor('Bench\PommBench');
        $this->map->createTable();
        $this->map->feedTable(1000);

        return $this;
    }

    public function __destruct()
    {
        $this->map->dropTable();
        parent::__destruct();
    }

    public function testPaginateQuery()
    {
        $this->diag('BaseObjectMap::paginateQuery()');
        $sql = "SELECT id FROM pomm_bench";
        $sql_count = "SELECT count(id) FROM pomm_bench";

        $pager = $this->map->paginateQuery($sql, $sql_count, array(), 100, 3);
        $this->isa_ok($pager, 'Pomm\Object\Pager', 'pager is a Pager instance.');
        $this->is($pager->getPage(), 3, "Page is 3.");
        $this->is($pager->getLastPage(), 10, 'There are 10 pages.');
        $this->is($pager->getResultMin(), 201, 'First result is 201.');
        $this->is($pager->getResultMax(), 300, 'Last result is 300.');
        $this->is($pager->getCount(), 1000, 'There are 1000 results.');
        $this->ok($pager->isNextPage(), 'There is a next page.');
        $this->ok($pager->isPreviousPage(), 'There is a previous page.');
        $collection = $pager->getCollection();
        $this->isa_ok($collection, 'Pomm\Object\Collection', 'getCollection returns a Collection.');

        $pager = $this->map->paginateQuery($sql, $sql_count, array(), 100, 1);
        $this->ok(!$pager->isPreviousPage(), 'There is NO previous page.');

        $pager = $this->map->paginateQuery($sql, $sql_count, array(), 100, 10);
        $this->ok(!$pager->isNextPage(), 'There is NO next page.');

        $pager = $this->map->paginateQuery($sql, $sql_count, array(), 80, 13);
        $this->is($pager->getLastPage(), 13, 'There are 15 pages.');
        $this->is($pager->getResultMin(), 961, 'First result is 961.');
        $this->is($pager->getResultMax(), 1000, 'Last result is 1000.');
        $this->ok(!$pager->isNextPage(), 'There is NO next page.');
        $this->ok($pager->isPreviousPage(), 'There is a previous page.');

        return $this;
    }

    public function testPaginateFindWhere()
    {
        $pager = $this->map->paginateFindWhere('id > ?', array(500), '', 100, 3);
        $this->isa_ok($pager, 'Pomm\Object\Pager', 'pager is a Pager instance.');
        $this->is($pager->getPage(), 3, "Page is 3.");
        $this->is($pager->getLastPage(), 5, 'There are 5 pages.');
        $this->is($pager->getResultMin(), 201, 'First result is 201.');
        $this->is($pager->getResultMax(), 300, 'Last result is 300.');
        $this->is($pager->getCount(), 500, 'There are 500 results.');
        $this->ok($pager->isNextPage(), 'There is a next page.');
        $this->ok($pager->isPreviousPage(), 'There is a previous page.');

        $pager = $this->map->paginateFindWhere(Where::create('id > ?', array(500)), array(), '', 100, 3);
        $this->isa_ok($pager, 'Pomm\Object\Pager', 'pager is a Pager instance.');
        $this->is($pager->getPage(), 3, "Page is 3.");
        $this->is($pager->getLastPage(), 5, 'There are 5 pages.');
        $this->is($pager->getResultMin(), 201, 'First result is 201.');
        $this->is($pager->getResultMax(), 300, 'Last result is 300.');
        $this->is($pager->getCount(), 500, 'There are 500 results.');
        $this->ok($pager->isNextPage(), 'There is a next page.');
        $this->ok($pager->isPreviousPage(), 'There is a previous page.');

        $pager = $this->map->paginateFindWhere('id > ?', array(500), 'ORDER BY data_int DESC, data_char ASC', 100, 3);
        $this->is($pager->getLastPage(), 5, 'There are 5 pages.');

        $pager = $this->map->paginateFindWhere('id > ?', array(1000), 'ORDER BY data_int DESC, data_char ASC', 100, 1);
        $this->is($pager->getPage(), 1, "Page is 1.");
        $this->is($pager->getLastPage(), 1, 'There is 1 page.');
        $this->is($pager->getResultMin(), 0, 'First result is 0.');
        $this->is($pager->getResultMax(), 0, 'Last result is 0.');
        $this->is($pager->getCount(), 0, 'There are 0 results.');
        $this->ok(!$pager->isNextPage(), 'There is NO next page.');
        $this->ok(!$pager->isPreviousPage(), 'There is NO previous page.');

        $pager = $this->map->paginateFindWhere('id <= ?', array(101), 'ORDER BY data_int DESC, data_char ASC', 100, 1);
        $this->is($pager->getPage(), 1, "Page is 1.");
        $this->is($pager->getLastPage(), 2, 'There are 2 pages.');
        $this->is($pager->getResultMin(), 1, 'First result is 1.');
        $this->is($pager->getResultMax(), 100, 'Last result is 100.');
        $this->is($pager->getCount(), 101, 'There are 101 results.');
        $this->ok($pager->isNextPage(), 'There is a next page.');
        $this->ok(!$pager->isPreviousPage(), 'There is NO previous page.');

        $pager = $this->map->paginateFindWhere('id <= ?', array(101), 'ORDER BY data_int DESC, data_char ASC', 100, 2);
        $this->is($pager->getResultMin(), 101, 'First result is 101.');
        $this->is($pager->getResultMax(), 101, 'Last result is 101.');
        $this->is($pager->getCount(), 101, 'There are 101 results.');
        $this->ok(!$pager->isNextPage(), 'There is NO next page.');
        $this->ok($pager->isPreviousPage(), 'There is a previous page.');
        return $this;
    }
}

$test = new PagerTest();

$test
    ->initialize()
    ->testPaginateQuery()
    ->testPaginateFindWhere()
    ;
