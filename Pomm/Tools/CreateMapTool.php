<?php

namespace Pomm\Tools;


/**
 * Pomm\Tools\CreateMapTool - Create a Map class
 *
 *
 * @uses Pomm\Tools\BaseTool
 * @package Pomm
 * @version $id$
 * @copyright 2011 - 2013 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class CreateMapTool extends CreateFileTool
{
    /**
     * configure()
     *
     * mandatory options :
     * * class      the class name to generate
     * * database   the database name
     *
     * optional options :
     * * namespace
     * * extends
     * * schema (default public)
     *
     * @see BaseTool
     */

    protected function configure()
    {
        parent::configure();
        $this->options->mustHave('class');
    }

    /**
     * execute()
     *
     * @see BaseTool
     */
    public function execute()
    {
        $content = $this->generateMapFile();
        $path = sprintf("%s/%sMap.php", $this->getDestinationPath(), $this->options['class']);
        $this->output_stack->add(sprintf("Create empty EntityMap class file '%s'.", $path), OutputLine::LEVEL_INFO);
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

use $namespace\\Base\\${class}Map as Base${class}Map;
use $namespace\\${class};
use \\Pomm\\Exception\\Exception;
use \\Pomm\\Query\\Where;

class ${class}Map extends Base${class}Map
{
}

EOD;

        return $php;
    }
}
