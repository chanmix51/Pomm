<?php
namespace Pomm\Tools;

use Pomm\Exception\ToolException;
use Pomm\Tools\ParameterHolder;

abstract class BaseTool
{
    protected $options;

    public final function __construct(Array $options = array())
    {
        $this->options = new ParameterHolder($options);

        $this->configure();
    }

    protected abstract function configure();
    public abstract function execute();
}
