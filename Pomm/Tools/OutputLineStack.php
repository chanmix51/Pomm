<?php
namespace Pomm\Tools;

use Pomm\Exception\ToolException;

class OutputLineStack implements \Iterator, \Countable
{
    public $stack = array();
    public $level;
    public $iterator = 0;

    /**
     * __construct
     *
     * @param Integer $level 
     **/
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
     **/
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
     **/
    public function addOutputLine(OutputLine $line)
    {
        if ( $line->getLevel() & $this->level )
        {
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
     **/
    public function setLevel($level)
    {
        $this->level = $level;
    }

    /**
     * getLevel
     *
     * @return Integer The stack's current severity level.
     **/
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * rewind
     *
     * @see \Iterator
     **/
    public function rewind()
    {
        $this->iterator = 0;
    }

    /**
     * current
     *
     * @see \Iterator
     **/
    public function current()
    {
        return $this->stack[$this->iterator];
    }

    /**
     * key
     *
     * @see \Iterator
     **/
    public function key()
    {
        return $this->iterator;
    }

    /**
     * next
     *
     * @see \Iterator
     **/
    public function next()
    {
        $this->iterator++;

        while ($this->valid() && ! ($this->stack[$this->iterator]->getLevel() & $this->level))
        {
            $this->iterator++;
        }
    }

    /**
     * valid
     *
     * @see \Iterator
     **/
    public function valid()
    {
        return $this->iterator < count($this->stack);
    }

    /**
     * count
     *
     * @see \Countable
     **/
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
     **/
    public function mergeStack(OutputLineStack $stack)
    {
        $this->stack = array_merge($this->stack, $stack->getStack());
    }

    /**
     * getStack
     *
     * @return OutputLineStack 
     **/
    public function getStack()
    {
        return $this->stack;
    }
}
