<?php

namespace Pomm\Tools;

use Pomm\Pomm;
use Pomm\Exception\Exception;
use Pomm\External\sfInflector;
use Pomm\Connection\Database;

/**
 * Pomm\Tools\ScanSchemaTool - Scan postgresql's schema to generate BaseMap
 * files
 *
 * @uses Pomm\Tools\BaseTool
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class ScanSchemaTool extends CreateFileTool
{
    /**
     * configure
     *
     * mandatory options :
     * * dir        the directory base classes will be generated in
     * * database   a Database instance
     *
     * @see Pomm\Tools\BaseTool
     **/
    protected function configure()
    {
        parent::configure();
        $this->options->setDefaultValue('extends', 'BaseObjectMap');
    }

    /**
     * execute()
     *
     * @see Pomm\Tools\BaseTool
     **/
    public function execute()
    {
        if (!($this->options['database'] instanceof Database))
        {
            throw new \InvalidArgumentException(sprintf('The database must be a "Pomm\Connection\Database" instance, "%s" given.', get_class($this->options['database'])));
        }

        $inspector = new Inspector($this->options['database']->createConnection());

        foreach ($inspector->getTablesInSchema($this->options['schema']) as $table_oid)
        {
            $this->output_stack->add(sprintf("Get table oid '%d'.", $table_oid));
            $this->options['oid'] = $table_oid;
            $tool = new CreateBaseMapTool($this->options->getParameters());

            $tool->execute();
            $this->output_stack->mergeStack($tool->getOutputStack());
        }

        $this->output_stack->add(sprintf("Finished scanning schema '%s'.", $this->options['schema']), OutputLine::LEVEL_INFO);
    }
}
