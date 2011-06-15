<?php

namespace Pomm\Tools;

use Pomm\Pomm;
use Pomm\Exception\Exception;
use Pomm\External\sfInflector;

/**
 * Pomm\Tools\CreateMapTool - Create a Map class
 *
 * 
 * @uses Pomm\Tools\BaseTool
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class CreateMapTool extends BaseTool
{
    /**
     * configure()
     * @see BaseTool
     *
     * mandatory options :
     * * dir        the directory base classes will be generated in
     * * class      the class name to generate
     *
     * optional options :
     * * namespace
     * * extends 
     **/

    protected function configure()
    {
        $this->options->mustHave('dir');
        $this->options->mustHave('class');
        $this->options->setDefaultValue('namespace', 'Model\Pomm\Entity');
        $this->options->setDefaultValue('schema', 'public');
    }

    /**
     * execute()
     * @see BaseTool
     **/
    public function execute()
    {
        $content = $this->generateMapFile();
        $this->saveMapFile($content);
    }

    /**
     * generateMapFile
     * Generates the empty map file
     *
     * @return void
     **/
    protected function generateMapFile()
    {
        $namespace = sprintf("%s\\%s", $this->options['namespace'], $this->options['schema']);
        $class =     $this->options['class'];

        $php = <<<EOD
<?php

namespace $namespace;

use $namespace\\Base\\${class}Map as Base${class}Map;
use Pomm\\Exception\\Exception;

class ${class}Map extends Base${class}Map
{
}
EOD;

        return $php;
    }

    /**
     * saveMapFile
     * Saves the map file content
     *
     * @args String The content of the map file
     **/
    protected function saveMapFile($content)
    {
        $filename = sprintf("%s/%s/%sMap.php", $this->options['dir'], sfInflector::camelize($this->options['schema']), $this->options['class']);
        $fh = fopen($filename, 'w');
        fputs($fh, $content);
        fclose($fh);
    }
}
