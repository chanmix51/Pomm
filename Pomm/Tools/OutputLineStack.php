<?php

namespace Pomm\Tools;

/**
 * Pomm\Tools\OutputLineStack
 *
 * @uses Pomm\Tools\BaseTool
 * @package Pomm
 * @version $id$
 * @copyright 2011 - 2013 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class OutputLineStack implements \Iterator, \Countable
{
    public $stack = array();
    public $level;
    public $iterator;

    /**
     * __construct
     *
     * @param Integer $level
     */
    public function __construct($level = OutputLine::LEVEL_ALL)
    {
        $this->setLevel($level);
    }

    /**
     * add
     *
     * Add a message in the stack with the given severity level.
     * This creates a OutputLine instance and stores it in the stack.
     *
     * @param String  $message
     * @param Integer $level ( default OutputLine::LEVEL_DEBUG )
     */
    public function add($message, $level = OutputLine::LEVEL_DEBUG)
    {
        $this->addOutputLine(new OutputLine($message, $level));
    }

    /**
     * addOutputLine
     *
     * Add a OutputLine instance in the stack.
     *
     * @param OutputLine $line
     */
    public function addOutputLine(OutputLine $line)
    {
        if ($line->getLevel() & $this->level) {
            $this->stack[] = $line;
        }
    }

    /**
     * setLevel
     *
     * Change the current severity level of the stack.
     * This changes the way the output lines are stored AND retrieved.
     *
     * @param Integer $level
     */
    public function setLevel($level)
    {
        $this->level = $level;
    }

    /**
     * getLevel
     *
     * @return Integer The stack's current severity level.
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * rewind
     *
     * @see \Iterator
     */
    public function rewind()
    {
        $this->iterator = null;
    }

    /**
     * current
     *
     * @see \Iterator
     */
    public function current()
    {
        if (is_null($this->iterator)) {
            $this->next();
        }

        return $this->stack[$this->iterator];
    }

    /**
     * key
     *
     * @see \Iterator
     */
    public function key()
    {
        return $this->iterator;
    }

    /**
     * next
     *
     * @see \Iterator
     */
    public function next()
    {
        if (!is_null($this->iterator)) {
            $this->iterator++;
        } else {
            $this->iterator = 0;
        }

        while ($this->valid() && ! ($this->stack[$this->iterator]->getLevel() & $this->level)) {
            $this->iterator++;
        }
    }

    /**
     * valid
     *
     * @see \Iterator
     */
    public function valid()
    {
        if (is_null($this->iterator)) {
            $this->next();
        }

        return $this->iterator < count($this->stack);
    }

    /**
     * count
     *
     * @see \Countable
     */
    public function count()
    {
        return count($this->stack);
    }

    /**
     * mergeStack
     *
     * Merges the stack from another OutputLineStack.
     *
     * @param OutputLineStack $stack
     */
    public function mergeStack(OutputLineStack $stack)
    {
        $this->stack = array_merge($this->stack, $stack->getStack());
    }

    /**
     * getStack
     *
     * @return OutputLineStack
     */
    public function getStack()
    {
        return $this->stack;
    }
}
