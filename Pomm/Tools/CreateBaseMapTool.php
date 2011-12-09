<?php

namespace Pomm\Tools;

use Pomm\External\sfInflector;
use Pomm\Tools\Inspector;

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
        $this->inspector = new Inspector($this->options['database']->createConnection());
        if (!$this->options->hasParameter('oid'))
        {
            $this->options->mustHave('table');
            $this->options['oid'] = $this->inspector->getTableOid($this->options['schema'], $this->options['table']);
        }
        else
        {
            $infos = $this->inspector->getTableInformation($this->options['oid']);
            $this->options['table'] = $infos['table'];
        }

        $this->options->setDefaultValue('class_name', sfInflector::camelize($this->options['table']));
        $this->options->setDefaultValue('extends', 'BaseObjectMap');
    }

    /**
     * execute()
     *
     * @see Pomm\Tools\BaseTool
     **/
    public function execute()
    {
        if (!($this->options['database'] instanceof \Pomm\Connection\Database))
        {
            throw new \InvalidArgumentException(sprintf('The database must be a "\Pomm\Connection\Database" instance, "%s" given.', get_class($this->options['database'])));
        }

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
        $primary_key = join(', ', $this->inspector->getTablePrimaryKey($this->options['oid']));
        $map_name   =  sprintf("%sMap", $class_name);

        if ($inherits = $this->inspector->getTableParents($this->options['oid']))
        {
            $parent_table_infos = $this->inspector->getTableInformation($inherits);
            $extends = sprintf("%s\\%sMap", $std_namespace, sfInflector::camelize($parent_table_infos['table']));
            $fields_definition = $this->generateFieldsDefinition(array_diff_key($this->inspector->getTableFieldsInformation($this->options['oid']), $this->inspector->getTableFieldsInformation($inherits)));
            $parent_call = "parent::intialize();\n";
        }
        else
        {
            $fields_definition = $this->generateFieldsDefinition($this->inspector->getTableFieldsInformation($this->options['oid']));
            $parent_call = "";
        }


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

        $parent_call
$fields_definition
        \$this->pk_fields = array($primary_key);
    }
}
EOD;

        return $php;
    }

    /**
     * generateFieldsDefinition - Generate the Pomm field definition for a
     * column
     *
     * @protected
     * @return string definiion
     **/
    protected function generateFieldsDefinition($attributes)
    {
        $fields_definition = "";

        foreach ($attributes as $attribute)
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

            $field_type = $this->options['database']->getConverterNameForType($format_type).$array_modifier;

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
