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
class Collection implements \Iterator, \Countable 
{
    protected $stmt;
    protected $object_map;
    protected $cache = false;
    protected $position = 0;
    protected $filters = array();
    protected $fetched = array();

    /**
     * __construct 
     * 
     * @param \PDOStatement              $stmt
     * @param \Pomm\Object\BaseObjectMap $object_map
     */
    public function __construct(\PDOStatement $stmt, \Pomm\Object\BaseObjectMap $object_map)
    {
        $this->stmt = $stmt;
        $this->object_map = $object_map;
        $this->position = $this->stmt === false ? null : 0;
    }

    /**
     * setCacheObjects
     *
     * Enable or disable fetched objects cache. This might be interesting if
     * you plan to iterate several times over a Collection. If disabled, the
     * CURSOR can not be reset so results can not be fetched a second time.
     * When enabled every result is kept in memory so this could lead to huge
     * memory consumption. If the option is set to true after any results
     * have been fetched, they are lost. The option is disabled by default the
     * class definition.
     *
     * @param Boolean defaut true
     **/

    public function setCacheObjects($flag = true)
    {
        $this->cache = $flag;
    }

    /**
     * __destruct
     *
     * Closes the cursor when the collection is cleared.
     **/
    public function __destruct()
    {
        $this->stmt->closeCursor();
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
        if ($this->cache === true && isset($this->fetched[$index]))
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
        $this->cache === true && $this->fetched[] = $fetched;

        return $fetched;
    }

    /**
     * has
     *
     * Return true if the given index exists false otherwise.
     *
     * @param Intger $index
     * @return Boolean
     **/

    public function has($index)
    {
        return $index < $this->count();
    }

    /**
     * count 
     * 
     * @see \Countable
     * @return Integer
     */
    public function count()
    {
        return $this->stmt->rowCount();
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

    /**
     * current 
     * 
     * @see \Iterator
     */
    public function current() 
    {
        return $this->get($this->position);
    }

    /**
     * key 
     * 
     * @see \Iterator
     */
    public function key() 
    {
        return $this->position;
    }

    /**
     * next 
     * 
     * @see \Iterator
     */
    public function next() 
    {
        ++$this->position;
    }

    /**
     * valid 
     * 
     * @see \Iterator
     * @return Boolean
     */
    public function valid() 
    {
        return $this->has($this->position);
    }

    /**
     * isFirst 
     * Is the iterator on the first element ?
     *
     * @return Boolean
     */
    public function isFirst()
    {
        return $this->position === 0;
    }

    /**
     * isLast 
     *
     * Is the iterator on the last element ?
     * 
     * @return Boolean
     */
    public function isLast()
    {
        return $this->position === $this->count() - 1;
    }

    /**
     * isEmpty 
     *
     * Is the collection empty (no element) ?
     * 
     * @return Boolean
     */
    public function isEmpty()
    {
        return $this->stmt->rowCount() === 0;
    }

    /**
     * isEven 
     *
     * Is the iterator on an even position ?
     * 
     * @return Boolean
     */
    public function isEven()
    {
        return ($this->position % 2) === 0;
    }

    /**
     * isOdd 
     *
     * Is the iterator on an odd position ?
     * 
     * @return Boolean
     */
    public function isOdd()
    {
        return ($this->position % 2) === 1;
    }

    /**
     * getOddEven 
     *
     * Return 'odd' or 'even' depending on the element index position.
     * Useful to style list elements when printing lists to do 
     * <li class="line_<?php $list->getOddEven() ?>">.
     * 
     * @return String
     */
    public function getOddEven()
    {
        return $this->position % 2 ? 'odd' : 'even';
    }

    /**
     * extract
     *
     * Return an array of results. Useful if you want to serialize a result or 
     * export it as a JSON format. Filters are still executed on all the 
     * fetched results.
     *
     * @param String $name The name of the resultset (Defaults to entity FQCN)
     * @return Array
     **/
    public function extract($name = null)
    {
        $name = is_null($name) ? $this->object_map->getObjectClass() : $name;
        $results = array();

        foreach ($this as $result)
        {
            $results[] = $result->extract();
        }

        return array($name => $results);
    }
}
