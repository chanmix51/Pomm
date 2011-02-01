<?php

namespace Pomm\Tool;

use Pomm\Pomm;

class CreateBaseMapTool extends BaseTool
{
    protected $db;
    protected $oid;
    protected $relname;
    protected $attributes = array();

    /**
     * configure()
     *
     * mandatory options :
     * * dir   the directory base classes will be generated in
     * * table the db table to be mapped
     * * dsn   the database connection to use
     **/
    protected function configure()
    {
        $this->checkOption('dir', true);
        $this->checkOption('table', true);
        $this->checkOption('dsn', true);

        Pomm::setDatabase(array('dsn' => $this->options['dsn']));
    }

    /**
     * getGeneralInfo()
     *
     * Set the general informations about a table (oid, name ...)
     * @access protected
     **/
    protected function getGeneralInfo()
    {
        $sql = sprintf("SELECT c.oid, n.nspname, c.relname FROM pg_catalog.pg_class c LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace WHERE c.relname ~ '^(%s)$' AND pg_catalog.pg_table_is_visible(c.oid) ORDER BY 2, 3;", $this->options['table']);
        $class = Pomm::executeAnonymousQuery($sql)->fetch();

        $this->oid = $class->oid;
        $this->relname = $class->relname;
    }

    /**
     * getAttributesInfo()
     *
     * get the columns informations
     * @access protected
     **/
    protected function getAttributesInfo()
    {
        $sql = sprintf("SELECT a.attname, pg_catalog.format_type(a.atttypid, a.atttypmod), (SELECT substring(pg_catalog.pg_get_expr(d.adbin, d.adrelid) for 128) FROM pg_catalog.pg_attrdef d WHERE d.adrelid = a.attrelid AND d.adnum = a.attnum AND a.atthasdef) as defaultval, a.attnotnull as notnull, a.attnum as index FROM pg_catalog.pg_attribute a WHERE a.attrelid = '%d' AND a.attnum > 0 AND NOT a.attisdropped ORDER BY a.attnum;", $this->oid);

        while ($class = Pomm::executeAnonymousQuery($sql)->fetch())
        {
            $this->attributes[] = $class;
        }
    }

    /**
     * execute
     * @see BaseTool
     **/
    public function execute()
    {
        $this->trans = Pomm::getDatabase()->createTransactionConnection();
        $this->getGeneralInfo();
        $this->getAttributesInfo();
    }
}
