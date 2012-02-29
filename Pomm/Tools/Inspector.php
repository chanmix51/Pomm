<?php

namespace Pomm\Tools;

use Pomm\Connection\Connection;
use Pomm\Exception\Exception;

class Inspector
{
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
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
        $oid = $this->connection->getPdo()->query($sql)->fetchColumn();

        if ($oid === FALSE)
        {
            throw new Exception(sprintf("Could not find table or view '%s' in postgres schema '%s'.", $table, $schema));
        }

        return $oid;
    }

    /**
     * getTableInformation 
     * Returns the object name, type and schema associated to an oid 
     *
     * @param Integer oid
     * @return Array the informations [table_oid, schema_oid, schema, name]
     **/
    public function getTableInformation($oid)
    {
        $sql = sprintf("SELECT c.oid AS table_oid, n.oid AS schema_oid, n.nspname AS \"schema\", c.relname AS \"table\" FROM pg_catalog.pg_class c JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace WHERE c.oid = %d;", $oid);
        $pdo = $this->connection->getPdo()->query($sql);

        $information = $pdo->fetch(\PDO::FETCH_ASSOC);

        if ($information === FALSE)
        {
            throw new Exception(sprintf("Could not find any objects in table pg_class for oid='%d'.", $oid));
        }

        return $information;
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
        $sql = <<<_
SELECT 
  a.attname,
  t.typname AS type,
  n.nspname AS type_namespace,
  (
    SELECT 
        substring(pg_catalog.pg_get_expr(d.adbin, d.adrelid) for 128) 
    FROM 
      pg_catalog.pg_attrdef d 
    WHERE 
        d.adrelid = a.attrelid 
      AND 
        d.adnum = a.attnum 
      AND 
        a.atthasdef
  ) as defaultval,
  a.attnotnull as notnull,
  a.attnum as index 
FROM 
  pg_catalog.pg_attribute a 
    JOIN pg_catalog.pg_type t ON
        a.atttypid = t.oid
    JOIN pg_namespace n ON
        t.typnamespace = n.oid
WHERE 
    a.attrelid = %d
  AND 
    a.attnum > 0 AND 
  NOT 
    a.attisdropped 
ORDER BY 
  a.attnum
_;
        $sql = sprintf($sql, $oid);

        $pdo = $this->connection->getPdo()->query($sql);
        $attributes = array();
        while ($class = $pdo->fetch(\PDO::FETCH_LAZY))
        {
            $attributes[] = array('attname' => $class->attname, 'format_type' => $class->type_namespace == 'pg_catalog' ? $class->type : sprintf("%s.%s", $class->type_namespace, $class->type));
        }

        return $attributes;
    }

    /**
     * getTableParent
     *
     * Return the oid of the parent table if any, FALSE is returned if there are no parent or if there are several 
     * parents.
     * @param Integer oid
     * @return Integer oid
     **/
    public function getTableParents($oid)
    {
        $sql = sprintf("SELECT pa.inhparent FROM pg_catalog.pg_inherits pa WHERE pa.inhrelid = %d", $oid);
        $pdo = $this->connection->getPdo()->query($sql);

        if ($pdo->rowCount() <> 1)
        {
            return FALSE;
        }

        return $pdo->fetchColumn();
    }


    /**
     * getTablesInSchema
     * Return the list of the tables within the schema
     *
     * @public
     * @param String schema name
     * @return Array tables OID
     **/
    public function getTablesInSchema($schema, Array $relkind = array('r', 'v'))
    {
        $sql = sprintf("SELECT c.oid FROM pg_catalog.pg_class c LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace WHERE c.relkind IN (%s) AND n.nspname = '%s'", sprintf("'%s'", join("', '", $relkind)), $schema);

        $tables = array();
        $pdo = $this->connection->getPdo()->query($sql);
        while ($oid = $pdo->fetchColumn())
        {
            $tables[] = $oid;
        }

        return $tables;
    }

    /**
     * getStoredProcedureSource
     * Return the source code of stored procedures
     *
     * @public
     * @param String schema name
     * @param String procedure name
     * @return Array source code
     **/
    public function getStoredProcedureSource($schema, $name)
    {
        $sql = sprintf("SELECT pg_catalog.pg_proc.prosrc FROM pg_catalog.pg_proc JOIN pg_catalog.pg_namespace ON pg_catalog.pg_proc.pronamespace = pg_catalog.pg_namespace.oid WHERE proname = '%s' AND pg_catalog.pg_namespace.nspname = '%s'", $name, $schema);

        $pdo = $this->connection->getPdo()->query($sql);
        $sources = array();

        while ($source = $pdo->fetchColumn())
        {
            $sources[] = $source;
        }

        return $sources;
    }
}
