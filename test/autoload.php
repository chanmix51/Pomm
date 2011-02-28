<?php

function __autoload($name) 
{
    $libs = array(
        'Pomm/Query/Where.php',
        'Pomm/Exception/SqlException.php',
        'Pomm/Exception/Exception.php',
        'Pomm/Type/StrType.php',
        'Pomm/Type/BoolType.php',
        'Pomm/Type/IntType.php',
        'Pomm/Type/TimestampType.php',
        'Pomm/Type/HStoreType.php',
        'Pomm/Type/BaseType.php',
        'Pomm/Type/LTreeType.php',
        'Pomm/Type/ArrayType.php',
        'Pomm/Object/Collection.php',
        'Pomm/Object/BaseObject.php',
        'Pomm/Object/BaseObjectMap.php',
        'Pomm/Pomm.php',
        'Pomm/Connection/Database.php', 
        'Pomm/Connection/Transaction.php', 
        'Pomm/Connection/Connection.php', 
        'Pomm/External/sfInflector.php',
        'Pomm/External/sfToolkit.php',
        'Pomm/Tools/ParameterHolder.php',
        'Pomm/Tools/BaseTool.php',
        'Pomm/Tools/CreateBaseMapTool.php',
);
    $class_name = array_pop(preg_split('/\\\/', $name));

    foreach ($libs as $lib)
    {
        if (strstr($lib, sprintf('/%s.php', $class_name)))
        {
            printf("Loading class '%s' in '%s'\n", $class_name, $lib);
            include(__DIR__."/../".$lib);
            return;
        }
    }
}

