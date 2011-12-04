<?php
namespace Pomm\Tools;

use Pomm\Exception\ToolException;
use Pomm\Tools\ParameterHolder;
use Pomm\External\sfInflector;

/**
 * CreateFileTool - Base class for creating files
 *
 * @abstract
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
abstract class CreateFileTool extends BaseTool
{
    /**
     * configure
     *
     * mandatory options
     * * database   a Database instance
     * * prefix_dir where to generate the dirs
     *
     * @see Pomm\Tools\BaseTool
     *
     **/
    protected function configure()
    {
        $this->options->mustHave('prefix_dir');
        $this->options->mustHave('database');
        $this->options->setDefaultValue('schema', 'public');
        $this->options->setDefaultValue('namespace', '');
    }

    /**
     * getDestinationPath
     *
     * create the final directory
     * @return String
     **/
    public function getDestinationPath()
    {
        $dir = array($this->options['prefix_dir'], sfInflector::camelize($this->options['database']->getName()));
        $schema_dir = sfInflector::camelize($this->options['schema']);

        if ($schema_dir === 'Public') 
        {
            $schema_dir = 'PublicSchema';
        }

        $dir[] = $schema_dir;

        return join(DIRECTORY_SEPARATOR, $dir);
    }

    /**
     * getNamespace()
     *
     * create the namespace
     * @return String
     **/
    protected function getNamespace()
    {
        $namespace = $this->options['namespace'] !== '' ? array($this->options['namespace']) : array();
        $namespace[] = sfInflector::camelize($this->options['database']->getName());
        $namespace[] = $this->options['schema'] == 'public' ? 'PublicSchema' : sfInflector::camelize($this->options['schema']);

        return join('\\', $namespace);
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
        if (!file_exists($dir)) 
        {
            return @mkdir($dir, 0755, true);
        }

        return true;
    }
}

