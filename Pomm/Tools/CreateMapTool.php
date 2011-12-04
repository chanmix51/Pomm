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
     * * class      the class name to generate
     *
     * optional options :
     * * namespace
     * * extends
     **/

    protected function configure()
    {
        $this->options->mustHave('class');
        $this->options->setDefaultValue('schema', 'public');
    }

    /**
     * execute()
     * @see BaseTool
     **/
    public function execute()
    {
        $content = $this->generateMapFile();
        $path = sprintf("%s/%sMap.php", $this->getDestinationPath(), $this->options['class']);
        $this->saveFile($path, $content);
    }

    /**
     * determine namespace
    /**
     * generateMapFile
     * Generates the empty map file
     *
     * @return void
     **/
    protected function generateMapFile()
    {
        $namespace = $this->getNamespace();
        $class =     $this->options['class'];

        $php = <<<EOD
<?php

namespace $namespace;

use $namespace\\Base\\${class}Map as Base${class}Map;
use $namespace\\${class};
use Pomm\\Exception\\Exception;
use Pomm\\Query\\Where;

class ${class}Map extends Base${class}Map
{
}
EOD;

        return $php;
    }
}
