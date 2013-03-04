<?php

namespace Pomm\Object;

/**
 * Pager
 *
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license MIT/X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class Pager
{
    protected $collection;
    protected $count;
    protected $max_per_page;
    protected $page;

    /**
     * __construct
     *
     * @param \Pomm\Object\Collection $collection
     * @param Integer $count         Total number of results.
     * @param Integer $max_per_page
     * @param Integer $page          Page index.
     **/
    public function __construct(\Pomm\Object\Collection $collection, $count, $max_per_page, $page)
    {
        $this->collection = $collection;
        $this->count = $count;
        $this->max_per_page = $max_per_page;
        $this->page = $page;
    }

    /**
     * getCollection
     *
     * Return the Pager's collection.
     *
     * @return \Pomm\Object\Collection
     **/
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * getResultCount
     *
     * Get the number of results in this page.
     *
     * @return Integer
     **/
    public function getResultCount()
    {
        return $this->count;
    }

    /**
     * getResultMin
     *
     * Get the index of the first element of this page.
     *
     * @return Integer
     **/
    public function getResultMin()
    {
        return min(( 1 + $this->max_per_page * ( $this->page - 1)), $this->count);
    }

    /**
     * getResultMax
     *
     * Get the index of the last element of this page.
     *
     * @return Integer
     **/
    public function getResultMax()
    {
        return max(($this->getResultMin() + $this->collection->count() - 1), 0);
    }

    /**
     * getLastPage
     *
     * Get the last page index.
     *
     * @return Integer
     **/
    public function getLastPage()
    {
        return $this->count == 0 ? 1 : ceil($this->count / $this->max_per_page);
    }

    /**
     * getPage
     *
     * @return Integer
     **/
    public function getPage()
    {
        return $this->page;
    }

    /**
     * isNextPage
     *
     * True if a next page exists.
     *
     * @return Boolean
     **/
    public function isNextPage()
    {
        return (bool) ($this->getPage() < $this->getLastPage());
    }

    /**
     * isPreviousPage
     *
     * True if a previous page exists.
     *
     * @return Boolean
     **/
    public function isPreviousPage()
    {
        return (bool) ($this->page > 1);
    }

    /**
     * getCount
     *
     * Get the total number of results in all pages.
     *
     * @return Boolean
     **/
    public function getCount()
    {
        return $this->count;
    }
}
