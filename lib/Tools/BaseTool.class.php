<?php
namespace Pomm\Tool;

use Pomm\Exception\ToolException;

abstract class BaseTool
{
    $this->options;

    public final function __construct(Array $options = array())
    {
        $this->options = $options;

        $this->configure();
    }

    protected abstract function configure();
    public abstract function execute();

    protected function checkOption($name, $mandatory = false, $default = null)
    {
        if (!in_array($name, $this->options))
        {
            if ($mandatory)
            {
                throw new ToolException(sprintf("The option '%s' is mandatory.", $name));
            }
            elseif (!is_null($default))
            {
                $this->options[$name] = $default;
            }
            else
            {
                return false;
            }
        }

        return true;
    }
}
