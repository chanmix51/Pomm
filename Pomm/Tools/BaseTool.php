<?php
namespace Pomm\Tools;

use Pomm\Exception\ToolException;
use Pomm\Tools\ParameterHolder;
use Pomm\External\sfInflector;

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

    public function getDestinationPath()
    {
        $schemaDir = sfInflector::camelize($this->options['schema']);
        if ($schemaDir === 'Public') {
            $schemaDir = 'PublicSchema';
        }
        if ($prefix = $this->options['prefix_dir']) {
            $prefix .= DIRECTORY_SEPARATOR;
        }
        $dir = $prefix.$schemaDir;

        return $dir;
    }

    protected function getNamespace()
    {
        $prefix = $this->options['namespace'];
        if ($prefix) {
            $prefix .= '\\';
        }
        $suffix = $this->options['schema'] == 'public' ? 'PublicSchema' : sfInflector::camelize($this->options['schema']);
        return sprintf('%s%s', $prefix, $suffix);
    }
    /**
     * saveFile - Save the file
     *
     * @param string the path
     * @param string the content to be saved
     * @return void
     **/
    protected function saveFile($path, $content)
    {
        $this->createDirIfNotExist(dirname($path));
        file_put_contents($path, $content);
    }

    /**
     * createDirIfNotExist
     * Create Entity model directory structure if it does no exist
     *
     * @return void;
     **/
    protected function createDirIfNotExist($dir)
    {
        if (!file_exists($dir)) {
            return @mkdir($dir, 0755, true);
        }

        return true;
    }


    /**
     * configure - This is called from the constructor. Override it to
     * configure the parameter holder.
     *
     * @abstract
     **/
    protected abstract function configure();

    /**
     * execute - Is called when the tool is to be executed.
     *
     * @abstract
     **/
    protected abstract function execute();
}
