<?php

namespace Pomm\Tools;

use Pomm\Pomm;

/**
 * Pomm\Tools\CreateEntityTool
 *
 * Create an Entity class from the database schema.
 *
 * @uses Pomm\Tools\BaseTool
 * @package Pomm
 * @version $id$
 * @copyright 2011 - 2013 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class CreateEntityTool extends CreateFileTool
{
    /**
     * configure()
     *
     * mandatory options :
     * * class      the class name to generate
     * * database   the database
     *
     * optional options :
     * * namespace
     * * schema (default public)
     * @see Pomm\Tools\BaseTool
     */
    protected function configure()
    {
        parent::configure();
        $this->options->mustHave('class');
    }

    /**
     * execute()
     * @see BaseTool
     */
    public function execute()
    {
        $content = $this->generateMapFile();
        $path = sprintf("%s/%s.php", $this->getDestinationPath(), $this->options['class']);
        $this->output_stack->add(sprintf("Create empty Entity class file '%s'.", $path), OutputLine::LEVEL_INFO);
        $this->saveFile($path, $content);
    }

    /**
     * generateMapFile
     *
     * Generates the empty map file.
     *
     * @access protected
     */
    protected function generateMapFile()
    {
        $namespace = $this->getNamespace();
        $class =     $this->options['class'];

        $php = <<<EOD
<?php

namespace $namespace;

use \\Pomm\\Object\\BaseObject;
use \\Pomm\\Exception\\Exception;

class $class extends BaseObject
{
}

EOD;

        return $php;
    }
}
