<?php
namespace Pomm\Tools;

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
    protected $output_stack;

    /**
     * __construct
     *
     * @final
     * @param Array $options (optional)
     */
    final public function __construct(Array $options = array())
    {
        $this->options = new ParameterHolder($options);
        $this->output_stack = new OutputLineStack($this->options->hasParameter('output_level') ? $this->options->getParameter('output_level') : OutputLine::LEVEL_ALL);

        $this->configure();
    }

    /**
     * configure
     *
     * This is called from the constructor. Override it to
     * configure the parameter holder.
     *
     * @abstract
     * @access protected
     */
    protected abstract function configure();

    /**
     * execute
     *
     * Is called when the tool is to be executed.
     *
     * @abstract
     * @access protected
     */
     abstract protected function execute();

    /**
     * getOutputStack
     *
     * @return OutputLineStack
     */
    public function getOutputStack()
    {
        return $this->output_stack;
    }
}
