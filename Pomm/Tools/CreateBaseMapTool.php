<?php

namespace Pomm\Tools;

use Pomm\External\sfInflector;
use Pomm\Tools\Inspector;
use Pomm\Exception\ToolException;

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
     * configure
     *
     * mandatory options:
     * * table or oid     the db table to be mapped
     * * database         a Database instance
     * * prefix_dir       where to generate the dirs
     *
     * other options:
     * * parent_namespace   override default namespace for parent
     * * namespace          the namespace format (default \%dbname%\%schema)
     *
     * @see Pomm\Tools\BaseTool
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

        $this->output_stack->add(sprintf("Table oid '%d' is '%s'.", $this->options['oid'], $this->options['table']));

        $this->options->setDefaultValue('class_name', sfInflector::camelize($this->options['table']));
        $this->options->setDefaultValue('extends', 'BaseObjectMap');
    }

    /**
     * execute
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
        $path = sprintf('%s%sBase%s%sMap.php', $this->getDestinationPath(), DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $this->options['class_name']);
        $this->output_stack->add(sprintf("Generating map file '%s' for table '%s.%s'.", $path, $this->options['schema'], $this->options['table']), OutputLine::LEVEL_INFO);
        $this->saveFile($path, $map_file);

        $this->output_stack->add(sprintf("Saving file '%s'.", $path));

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

            if ($this->options->hasParameter('parent_namespace'))
            {
                $extends = sprintf("\\%s\\%sMap", $this->parseNamespace($this->options['parent_namespace']), sfInflector::camelize($parent_table_infos['table']));
            }
            else
            {
                $extends = sprintf("\\%s\\%sMap",
                    $this->getNamespace(),
                    sfInflector::camelize($parent_table_infos['table']));
            }

            $this->output_stack->add(sprintf("Detected inheritance to table '%s'.", $parent_table_infos['table']));
            $fields_definition = $this->generateFieldsDefinition(array_udiff(
                $this->inspector->getTableFieldsInformation($this->options['oid']),
                $this->inspector->getTableFieldsInformation($inherits),
                function($a, $b) { return strcasecmp($a['attname'], $b['attname']); }
            ));
            $parent_call = "        parent::initialize();\n";
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
$parent_call
        \$this->object_class =  '$std_namespace\\$class_name';
        \$this->object_name  =  '$table_name';

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

            if (preg_match('/^([\w]+\.)?_(.+)$/', $attribute['format_type'], $matchs))
            {
                $field_type = sprintf("%s%s[]", $matchs[1], $matchs[2]);
            }
            else
            {
                $field_type = $attribute['format_type'];
            }

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
       $file = sprintf("%s%s%s.php", $this->getDestinationPath(), DIRECTORY_SEPARATOR, $this->options['class_name']);
       if (!file_exists($file))
       {
           $this->output_stack->add(sprintf("Create Entity class file."));
           $tool = new CreateEntityTool(array(
               'prefix_dir' => $this->options['prefix_dir'],
               'class'      => $this->options['class_name'],
               'namespace'  => $this->options['namespace'],
               'schema'     => $this->options['schema'],
               'database'   => $this->options['database']
           ));
           $tool->execute();
           $this->output_stack->mergeStack($tool->getOutputStack());
       }

       $file = sprintf("%s%s%sMap.php", $this->getDestinationPath(), DIRECTORY_SEPARATOR, $this->options['class_name']);
       if (!file_exists($file))
       {
           $this->output_stack->add(sprintf("Create EntityMap class file."));
           $tool = new CreateMapTool(array(
               'prefix_dir' => $this->options['prefix_dir'],
               'class'      => $this->options['class_name'],
               'namespace'  => $this->options['namespace'],
               'schema'     => $this->options['schema'],
               'database'   => $this->options['database']
           ));
           $tool->execute();
           $this->output_stack->mergeStack($tool->getOutputStack());
       }
    }

    public function getOutputLineStack()
    {
        return $this->output_stack;
    }
}
