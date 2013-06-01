<?php
namespace Pomm\Tools;

use Pomm\Exception\ToolException;
use Pomm\Tools\Inflector;

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
     * options with default values
     * * schema     "public" the schema to search tables or views
     * * namespace  "\%dbname%\%schema%" the namespace string format
     *
     * @see Pomm\Tools\BaseTool
     */
    protected function configure()
    {
        $this->options->mustHave('prefix_dir');
        $this->options->mustHave('database');
        $this->options->setDefaultValue('schema', 'public');
        $this->options->setDefaultValue('namespace', '%dbname%\%schema%');
    }

    /**
     * getDestinationPath
     *
     * Create the final directory.
     *
     * @return String
     */
    public function getDestinationPath()
    {
        return $this->options['prefix_dir'].DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $this->getNamespace());
    }

    /**
     * getNamespace()
     *
     * Get the namespace from given option namespace.
     *
     * @return String
     */
    protected function getNamespace()
    {
        return $this->parseNamespace($this->options['namespace']);
    }

    /**
     * parseNamespace
     *
     * Return a well formatted namespace from the given namespace string.
     * Currently accepting the following placeholders:
     * * %dbname%   will be replaced with the database's name.
     * * %schema%   will be replaced with the schema name.
     *
     * @param string string
     * @return string
     */
    protected function parseNamespace($string)
    {
        $string = str_replace('%dbname%', Inflector::camelize($this->options['database']->getName()), $string);
        $string = str_replace('%schema%', $this->options['schema'] == 'public' ? 'PublicSchema' : Inflector::camelize($this->options['schema']), $string);

        return $string;
    }

    /**
     * saveFile 
     *
     * Save the file.
     *
     * @param String $path
     * @param String $content
     */
    protected function saveFile($path, $content)
    {
        if (!$this->createDirIfNotExist(dirname($path)))
        {
            throw new ToolException(sprintf("Could not create directories for file '%s'.", $path));
        }

        file_put_contents($path, $content);
    }

    /**
     * createDirIfNotExist
     *
     * Create Entity model directory structure if it does no exist.
     *
     * @param String $dir
     */
    protected function createDirIfNotExist($dir)
    {
        if (!file_exists($dir))
        {
            $this->output_stack->add(sprintf("Create directory '%s'.", $dir));

            return @mkdir($dir, 0755, true);
        }

        return true;
    }
}
