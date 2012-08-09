<?php
namespace Pomm\Tools;

use Pomm\Exception\ToolException;

class OutputLineStack implements \Iterator, \Countable
{
    public $stack = array();
    public $level;
    public $iterator = 0;

    public function __construct($level = OutputLine::LEVEL_ALL)
    {
        $this->setLevel($level);
    }

    public function add($message, $level = OutputLine::LEVEL_DEBUG)
    {
        $this->addOutputLine(new OutputLine($message, $level));
    }

    public function addOutputLine(OutputLine $line)
    {
        if ( $line->getLevel() & $this->level )
        {
            $this->stack[] = $line;
        }
    }

    public function setLevel($level)
    {
        $this->level = $level;
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function rewind()
    {
        $this->iterator = 0;
    }

    public function current()
    {
        return $this->stack[$this->iterator];
    }

    public function key()
    {
        return $this->iterator;
    }

    public function next()
    {
        $this->iterator++;

        while ($this->valid() && ! ($this->stack[$this->iterator]->getLevel() & $this->level))
        {
            $this->iterator++;
        }
    }

    public function valid()
    {
        return $this->iterator < count($this->stack);
    }

    public function count()
    {
        return count($this->stack);
    }

    public function mergeStack(OutputLineStack $stack)
    {
        $this->stack = array_merge($this->stack, $stack->getStack());
    }

    public function getStack()
    {
        return $this->stack;
    }
}
