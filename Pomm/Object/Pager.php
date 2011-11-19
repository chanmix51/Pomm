<?php

namespace Pomm\Object;

use Pomm\Exception\Exception;

class Pager
{
    protected $collection;
    protected $count;
    protected $max_per_page;
    protected $page;

    public function __construct(\Pomm\Object\Collection $collection, $count, $max_per_page, $page)
    {
        $this->collection = $collection;
        $this->count = $count;
        $this->max_per_page = $max_per_page;
        $this->page = $page;
    }

    public function getCollection()
    {
        return $this->collection;
    }

    public function getResultCount()
    {
        return $this->count;
    }

    public function getResultMin()
    {
        return min(( 1 + $this->max_per_page * ( $this->page - 1)), $this->count);
    }

    public function getResultMax()
    {
        return max(($this->getResultMin() + $this->collection->count() - 1), 0);
    }

    public function getLastPage()
    {
        return $this->count == 0 ? 1 : ceil($this->count / $this->max_per_page);
    }

    public function getPage()
    {
        return $this->page;
    }

    public function isNextPage()
    {
        return (bool) ($this->getPage() < $this->getLastPage());
    }

    public function isPreviousPage()
    {
        return (bool) ($this->page > 1);
    }

    public function getCount()
    {
        return $this->count;
    }
}
