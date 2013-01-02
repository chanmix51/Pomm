<?php

namespace Pomm\Object;

use \Pomm\Exception\Exception;

/**
 * Collection 
 * 
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license MIT/X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class Collection extends SimpleCollection
{
    protected $filters = array();
    protected $fetched = array();


    /**
     * __sleep
     * 
     * clean the object before serializing it
     * 
     **/
    public function __sleep()
    {
        return array('position', 'filters', 'fetched');
    }
    
    /**
     * __destruct 
     * 
     * only call the parent function if stmt is set
     * 
     **/
    public function __destruct()
    {
        if (isset($this->stmt)) {
    	    parent::__destruct();
        }
    }
    
    /**
     * registerFilter
     *
     * Register a callable as filter.
     *
     * @param Callable $callable May be anonymous function.
     * @return \Pomm\Object\Collection
     **/

    public function registerFilter($callable)
    {
        $this->filters[] = $callable;

        return $this;
    }

    /**
     * unregisterFilter
     *
     * Unregister a filter.
     *
     * @param Callable the callable to unregister.
     * @return \Pomm\Object\Collection
     **/
    public function unregisterFilter($callable)
    {
        $this->filters = array_map(function($value) use ($callable) {
            return $value == $callable ? $value : null;
        }, $this->filters);

        return $this;
    }

    /**
     * resetFilters
     *
     * Remove all filters.
     *
     * @return \Pomm\Object\Collection
     **/
    public function resetFilters()
    {
        $this->filters = array();

        return $this;
    }

    /**
     * get
     *
     * Return a particular result.
     *
     * @param Integer $index
     * @return \Pomm\Object\BaseObject
     **/

    public function get($index)
    {
        if (isset($this->fetched[$index]))
        {
            return $this->fetched[$index];
        }

        $values = $this->stmt->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_ABS, $index);

        if ($values === false) 
            return false;

        foreach($this->filters as $index => $filter)
        {
            $values = $filter($values);
            if (!is_array($values))
            {
                throw new Exception(sprintf("Filters have to return an Array. Filter number %d returned a '%s'.", $index, gettype($values)));
            }
        }

        $fetched = $this->object_map->createObjectFromPg($values);
        $this->fetched[] = $fetched;

        return $fetched;
    }

    /**
     * rewind 
     * 
     * @see \Iterator
     */
    public function rewind() 
    {
        $this->position = 0;
    }
}
