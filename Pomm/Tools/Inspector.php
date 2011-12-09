<?php

namespace Pomm\Tools;

use Pomm\Connection\Connection;
use Pomm\Exception\Exception;

class Inspector
{
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->database = $connection;
    }

    /**
     * getTableOid
     * Returns a table oid
     *
     * @param String schema
     * @param String table
     * @return Integer oid 
     **/
    public function getTableOid($schema, $table)
    {
        $sql = sprintf("SELECT c.oid FROM pg_catalog.pg_class c LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace WHERE n.nspname = '%s' AND c.relname ~ '^(%s)$';", $schema, $table);
        $oid = $this->transaction->getPdo()->query($sql)->fetchColumn();

        if ($oid === FALSE)
        {
            throw new Exception(sprintf("Could not find table or view '%s' in postgres schema '%s'.", $table, $schema));
        }

        return $oid;
    }

    /**
     * getTablePrimaryKey
     *
     * returns an array with the fields composing a primary key.
     * @param Intger oid
     * @return Array
     **/
    public function getTablePrimaryKey($oid)
    {
        $sql = sprintf("SELECT regexp_matches(pg_catalog.pg_get_indexdef(i.indexrelid, 0, true), e'\\\\((.*)\\\\)', 'gi') AS pkey FROM pg_catalog.pg_class c, pg_catalog.pg_class c2, pg_catalog.pg_index i WHERE c.oid = '%d' AND c.oid = i.indrelid AND i.indexrelid = c2.oid ORDER BY i.indisprimary DESC, i.indisunique DESC, c2.relname", $oid);
        $pkey = $this->connection->getPdo()->query($sql)->fetch(\PDO::FETCH_NAMED);

        $pkey = preg_split('/, /', trim($pkey['pkey'], '["{}]'));
        array_walk($pkey, function(&$value) { $value = sprintf("'%s'", $value); });

        return $pkey;
    }

    /**
     * getTableFieldsInformation()
     *
     * get the columns informations
     * @param Integer oid
     * @return Array key is the column name, value is the type.
     **/
    public function getTableFieldsInformation($oid)
    {
        $sql = sprintf("SELECT a.attname, pg_catalog.format_type(a.atttypid, a.atttypmod), (SELECT substring(pg_catalog.pg_get_expr(d.adbin, d.adrelid) for 128) FROM pg_catalog.pg_attrdef d WHERE d.adrelid = a.attrelid AND d.adnum = a.attnum AND a.atthasdef) as defaultval, a.attnotnull as notnull, a.attnum as index FROM pg_catalog.pg_attribute a WHERE a.attrelid = '%d' AND a.attnum > 0 AND NOT a.attisdropped ORDER BY a.attnum;", $oid);

        $pdo = $this->transaction->getPdo()->query($sql);
        $attributes = array();
        while ($class = $pdo->fetch(\PDO::FETCH_LAZY))
        {
            $attributes[] = array('attname' => $class->attname, 'format_type' => $class->format_type);
        }

        return $attributes;
    }
}
