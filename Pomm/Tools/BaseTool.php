<?php
namespace Pomm\Tools;

use Pomm\Exception\ToolException;
use Pomm\Tools\ParameterHolder;

/**
 * BaseTool - Base class for tools.
 * 
 * @abstract
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
abstract class BaseTool
{
    protected $options;

    /**
     * __construct
     *
     * @final
     * @public
     * @param Array options
     **/
    public final function __construct(Array $options = array())
    {
        $this->options = new ParameterHolder($options);

        $this->configure();
    }

    /**
     * configure - This is called from the constructor. Override it to 
     * configure the parameter holder.
     *
     * @protected
     * @abstract
     **/
    protected abstract function configure();

    /**
     * execute - Is called when the tool is to be executed.
     *
     * @public
     * @abstract
     **/
    public abstract function execute();
}
