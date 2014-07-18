<?php

namespace Pomm\Tools;

use Pomm\Pomm;
use Pomm\Connection\Database;

/**
 * Pomm\Tools\ScanSchemaTool - Scan postgresql's schema to generate BaseMap
 * files
 *
 * @uses Pomm\Tools\BaseTool
 * @package Pomm
 * @version $id$
 * @copyright 2011 - 2013 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class ScanSchemaTool extends CreateFileTool
{
    /**
     * configure
     *
     * mandatory options :
     * * prefix_dir the directory base classes will be generated in
     * * database   a Database instance
     *
     * other options
     * * parent_namespace   override default namespace for parent
     * * namespace          the namespace format (default \%dbname%\%schema)
     * * class_name         the corresponding entity class name
     *                      (default: table's camel cased name).
     * * extends            The class the BaseMap should extend
     *                      (default: \Pomm\Object\BaseObjectMap)
     * * exclude            An array of tables/views to skip.
     *
     * @see Pomm\Tools\BaseTool
     */
    protected function configure()
    {
        parent::configure();
        $this->options->setDefaultValue('extends', 'BaseObjectMap');
    }

    /**
     * execute()
     *
     * @see Pomm\Tools\BaseTool
     */
    public function execute()
    {
        if (!($this->options['database'] instanceof Database)) {
            throw new \InvalidArgumentException(sprintf('The database must be a "Pomm\Connection\Database" instance, "%s" given.', get_class($this->options['database'])));
        }

        $inspector = new Inspector($this->options['database']->getConnection());

        $noTables = $this->options->hasParameter('exclude') ?  array_flip($inspector->getTablesOids($this->options['schema'], $this->options['exclude'])) : array();

        foreach ($inspector->getTablesInSchema($this->options['schema']) as $tableOid) {
            $this->outputStack->add(sprintf("Get table oid '%d'.", $tableOid));
            if (array_key_exists($tableOid, $noTables)) {
                $this->outputStack->add(sprintf("Table '%s' (oid %d) excluded.", $noTables[$tableOid], $tableOid));
                continue;
            }

            $this->options['oid'] = $tableOid;
            $tool = new CreateBaseMapTool($this->options->getParameters());

            $tool->execute();
            $this->outputStack->mergeStack($tool->getOutputStack());
        }

        $this->outputStack->add(sprintf("Finished scanning schema '%s'.", $this->options['schema']), OutputLine::LEVEL_INFO);
    }
}
