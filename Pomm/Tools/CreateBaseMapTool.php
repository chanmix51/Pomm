<?php

namespace Pomm\Tools;

use Pomm\Pomm;
use Pomm\Exception\Exception;
use Pomm\External\sfInflector;
use Pomm\Connection\Database;

class CreateBaseMapTool extends BaseTool
{
    protected $db;
    protected $oid;
    protected $relname;
    protected $attributes = array();
    protected $transaction;

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
        $this->options->mustHave('table');
        $this->options->mustHave('connection');
        $this->options->setDefaultValue('class_name', sfInflector::camelize($this->options['table']));
        $this->options->setDefaultValue('namespace', 'Model\Pomm\Map');
        $this->options->setDefaultValue('extends', 'BaseObjectMap');
        $this->options->setDefaultValue('schema', 'public');
    }

    /**
     * getGeneralInfo()
     *
     * Set the general informations about a table (oid, name ...)
     * @access protected
     **/
    protected function getGeneralInfo()
    {
        $sql = sprintf("SELECT c.oid, c.relname FROM pg_catalog.pg_class c LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace WHERE n.nspname = '%s' AND c.relname ~ '^(%s)$' ORDER BY 2;", $this->options['schema'], $this->options['table']);
        $class = $this->transaction->getPdo()->query($sql)->fetch(\PDO::FETCH_LAZY);

        if (!$class)
        {
            throw new Exception(sprintf("Could not find table or view '%s' in postgres schema '%s'.", $this->options['table'], $this->options['schema']));
        }

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

        $pdo = $this->transaction->getPdo()->query($sql);
        $this->attribute = array();
        while ($class = $pdo->fetch(\PDO::FETCH_LAZY))
        {
            $this->attributes[] = array('attname' => $class->attname, 'format_type' => $class->format_type);
        }
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
        $this->getGeneralInfo();
        $this->getAttributesInfo();

        $map_file = $this->generateMapFile();
        $this->saveMapFile($map_file);
    }

    protected function generateMapFile()
    {
        $namespace   = $this->options['namespace'];
        $class_name  = $this->options['schema'] == 'public' ? $this->options['class_name'] : sfInflector::camelize($this->options['schema']).$this->options['class_name'];
        $table_name  = sprintf("%s.%s", $this->options['schema'], $this->options['table']);
        $extends     = $this->options['extends'];
        $primary_key = $this->getPrimaryKey();
        $fields_definitions = $this->generateFieldsDefinition();
        $map_name   =  sprintf("Base%sMap", $class_name);

        $php = <<<EOD
<?php

namespace $namespace;

use Pomm\\Object\\BaseObjectMap;
use Pomm\\Object\\BaseObject;
use Pomm\\Exception\\Exception;

class $map_name extends $extends
{
    public function initialize()
    {
        \$this->object_class =  '$class_name';
        \$this->object_name  =  '$table_name';

$fields_definitions
        \$this->pk_fields = array($primary_key);
    }
}
EOD;

        return $php;
    }

    protected function getPrimaryKey()
    {
        $sql = sprintf("SELECT regexp_matches(pg_catalog.pg_get_indexdef(i.indexrelid, 0, true), e'\\\\((.*)\\\\)', 'gi') AS pkey FROM pg_catalog.pg_class c, pg_catalog.pg_class c2, pg_catalog.pg_index i WHERE c.oid = '%d' AND c.oid = i.indrelid AND i.indexrelid = c2.oid ORDER BY i.indisprimary DESC, i.indisunique DESC, c2.relname", $this->oid);
        $pkey = $this->transaction->getPdo()->query($sql)->fetch(\PDO::FETCH_NAMED);

        $pkey = preg_split('/, /', trim($pkey['pkey'], '["{}]'));
        array_walk(&$pkey, function(&$value) { $value = sprintf("'%s'", $value); }); 

        return join($pkey, ", ");
    }

    public function generateFieldsDefinition()
    {
        $fields_definition = "";

        foreach ($this->attributes as $attribute)
        {
            $field_name = $attribute['attname'];

            if (preg_match('/^(.+)\[\]$/', $attribute['format_type'], $matchs))
            {
                $array_modifier = '[]';
                $format_type = $matchs[1];
            }
            else
            {
                $array_modifier = '';
                $format_type = $attribute['format_type'];
            }

            $field_type = $this->transaction->getDatabase()->getConverterNameForType($format_type).$array_modifier;

            $fields_definition .= sprintf("        \$this->addField('%s', '%s');\n", $field_name, $field_type);
        }

        return $fields_definition;
    }

    public function guessFromType($type)
    {
        $types = array('IntType' => 'integer', 'BoolType' => 'boolean', 'StrType' => 'character|text', 'TimestampType' => 'timestamp', 'LTreeType' => 'ltree', 'HStoreType' => 'hstore');

        foreach ($types as $pomm_type => $pattern)
        {
            $regexp = sprintf("/%s/i", $pattern);
            if (preg_match($regexp, $type))
            {
                return $pomm_type;
            }
        }

        throw new Exception(sprintf("Unknown type '%s'.", $type));
    }

    public function saveMapFile($content)
    {
        $schema_prefix = $this->options['schema'] == 'public' ? '' : sfInflector::camelize($this->options['schema']);
        $filename = sprintf("%s/Base%s%sMap.php",$this->options['dir'], $schema_prefix, $this->options['class_name']);
        $fh = fopen($filename, 'w');
        fputs($fh, $content);
        fclose($fh);
    }
}
