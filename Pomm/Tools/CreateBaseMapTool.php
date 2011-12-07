<?php

namespace Pomm\Tools;

use Pomm\Service;
use Pomm\Exception\Exception;
use Pomm\External\sfInflector;
use Pomm\Connection\Database;

/**
 * Pomm\Tools\CreateBaseMapTool - Create a BaseMap class from the database.
 *
 *
 * @uses Pomm\Tools\BaseTool
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class CreateBaseMapTool extends CreateFileTool
{
    protected $db;
    protected $oid;
    protected $relname;
    protected $attributes = array();
    protected $transaction;

    /**
     * configure()
     * mandatory options :
     * * table      the db table to be mapped
     * * database   a Database instance
     * * prefix_dir where to generate the dirs
     *
     * @see Pomm\Tools\BaseTool
     *
     **/
    protected function configure()
    {
        parent::configure();
        $this->options->mustHave('table');

        $this->options->setDefaultValue('class_name', sfInflector::camelize($this->options['table']));
        $this->options->setDefaultValue('extends', 'BaseObjectMap');
    }

    /**
     * getGeneralInfo()
     * Set the general informations about a table (oid, name ...)
     *
     * @protected
     * @return void
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
     * @protected
     * @return void
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
     *
     * @see Pomm\Tools\BaseTool
     **/
    public function execute()
    {
        if (!($this->options['database'] instanceof Database))
        {
            throw new \InvalidArgumentException(sprintf('The database must be a "Pomm\Connection\Database" instance, "%s" given.', get_class($this->options['database'])));
        }
        $this->transaction = $this->options['database']->createConnection();
        $this->getGeneralInfo();
        $this->getAttributesInfo();

        $map_file = $this->generateMapFile();
        $path = sprintf('%s/Base/%sMap.php', $this->getDestinationPath(), $this->options['class_name']);
        $this->saveFile($path, $map_file);
        $this->createEmptyFilesIfNotExist();
    }

    /**
     * generateMapFile - Generate Map file PHP code
     *
     * @protected
     * @return string the PHP code
     **/
    protected function generateMapFile()
    {
        $std_namespace = $this->getNamespace();
        $namespace       = $std_namespace.'\\Base';
        $class_name  = $this->options['class_name'];
        $table_name  = sprintf("%s.%s", $this->options['schema'], $this->options['table']);
        $extends     = $this->options['extends'];
        $primary_key = $this->getPrimaryKey();
        $fields_definitions = $this->generateFieldsDefinition();
        $map_name   =  sprintf("%sMap", $class_name);

        $php = <<<EOD
<?php

namespace $namespace;

use \\Pomm\\Object\\BaseObjectMap;
use \\Pomm\\Exception\\Exception;

abstract class $map_name extends $extends
{
    public function initialize()
    {
        \$this->object_class =  '$std_namespace\\$class_name';
        \$this->object_name  =  '$table_name';

$fields_definitions
        \$this->pk_fields = array($primary_key);
    }
}
EOD;

        return $php;
    }

    /**
     * getPrimaryKey - Return the primary key
     *
     * @protected
     * @return Array  The primary key
     **/
    protected function getPrimaryKey()
    {
        $sql = sprintf("SELECT regexp_matches(pg_catalog.pg_get_indexdef(i.indexrelid, 0, true), e'\\\\((.*)\\\\)', 'gi') AS pkey FROM pg_catalog.pg_class c, pg_catalog.pg_class c2, pg_catalog.pg_index i WHERE c.oid = '%d' AND c.oid = i.indrelid AND i.indexrelid = c2.oid ORDER BY i.indisprimary DESC, i.indisunique DESC, c2.relname", $this->oid);
        $pkey = $this->transaction->getPdo()->query($sql)->fetch(\PDO::FETCH_NAMED);

        $pkey = preg_split('/, /', trim($pkey['pkey'], '["{}]'));
        array_walk($pkey, function(&$value) { $value = sprintf("'%s'", $value); });

        return join($pkey, ", ");
    }

    /**
     * generateFieldsDefinition - Generate the Pomm field definition for a
     * column
     *
     * @public
     * @return string definiion
     **/
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

    /**
     * createEmptyFilesIfNotExist
     * Create empty map and entity class if they do not exist
     **/
    protected function createEmptyFilesIfNotExist()
    {
       $file = sprintf("%s/%s.php", $this->getDestinationPath(), $this->options['class_name']);
       if (!file_exists($file))
       {
           $tool = new CreateEntityTool(array(
               'prefix_dir' => $this->options['prefix_dir'],
               'class'      => $this->options['class_name'],
               'namespace'  => $this->options['namespace'],
               'schema'     => $this->options['schema'],
               'database'   => $this->options['database']
           ));
           $tool->execute();
       }

       $file = sprintf("%s/%sMap.php", $this->getDestinationPath(), $this->options['class_name']);
       if (!file_exists($file))
       {
           $tool = new CreateMapTool(array(
               'prefix_dir' => $this->options['prefix_dir'],
               'class'      => $this->options['class_name'],
               'namespace'  => $this->options['namespace'],
               'schema'     => $this->options['schema'],
               'database'   => $this->options['database']
           ));
           $tool->execute();
       }
    }
}
