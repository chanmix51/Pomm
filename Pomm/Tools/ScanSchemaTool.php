<?php

namespace Pomm\Tools;

use Pomm\Pomm;
use Pomm\Exception\Exception;
use Pomm\External\sfInflector;
use Pomm\Connection\Database;

class ScanSchemaTool extends BaseTool
{
    /**
     * configure()
     * @see BaseTool
     *
     * mandatory options :
     * * dir        the directory base classes will be generated in
     * * table      the db table to be mapped
     * * connection a Connection instance
     **/
    protected function configure()
    {
        $this->options->mustHave('dir');
        $this->options->mustHave('schema');
        $this->options->mustHave('connection');
        $this->options->setDefaultValue('namespace', 'Model\Pomm\Map');
        $this->options->setDefaultValue('extends', 'BaseObjectMap');
    }

    /**
     * execute()
     * @see BaseTool
     **/
    public function execute()
    {
        if (!($this->options['connection'] instanceof Database))
        {
            throw new \InvalidArgumentException(sprintf('The connection must be a "Pomm\Connection\Database" instance, "%s" given.', get_class($this->options['connection'])));
        }

        $this->transaction = $this->options['connection']->createTransaction();

        foreach ($this->getTables() as $table)
        {
            $this->options['table'] = $table;
            $tool = new CreateBaseMapTool($this->options->getParameters());

            $tool->execute();
        }
    }

    protected function getTables()
    {
        $sql = sprintf("SELECT c.relname FROM pg_catalog.pg_class c LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace WHERE c.relkind='r' AND n.nspname = '%s'", $this->options['schema']);

        $tables = array();
        $pdo = $this->transaction->getPdo()->query($sql);
        while ($table = $pdo->fetch(\PDO::FETCH_LAZY))
        {
            $tables[] = $table->relname;
        }

        return $tables;
    }
}
