<?php

namespace Pomm\Tools;

use Pomm\Pomm;
use Pomm\Exception\Exception;
use Pomm\External\sfInflector;

/**
 * Pomm\Tools\CreateEntityTool - Create an Entity class
 *
 *
 * @uses Pomm\Tools\BaseTool
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class CreateEntityTool extends BaseTool
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
     * * schema
     **/

    protected function configure()
    {
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
        $path = sprintf("%s/%s.php", $this->getDestinationPath(), $this->options['class']);
        $this->saveFile($path, $content);
    }

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

use Pomm\\Object\\BaseObject;
use Pomm\\Exception\\Exception;

class $class extends BaseObject
{
}
EOD;

        return $php;
    }
}
