<?php

function __autoload($name) 
{
    $libs = array(
        'Pomm/Query/Where.class.php',
        'Pomm/Exception/SqlException.class.php',
        'Pomm/Exception/Exception.class.php',
        'Pomm/Type/StrType.class.php',
        'Pomm/Type/BoolType.class.php',
        'Pomm/Type/IntType.class.php',
        'Pomm/Type/TimestampType.class.php',
        'Pomm/Type/HStoreType.class.php',
        'Pomm/Type/BaseType.class.php',
        'Pomm/Type/LTreeType.class.php',
        'Pomm/Type/ArrayType.class.php',
        'Pomm/Object/Collection.class.php',
        'Pomm/Object/BaseObject.class.php',
        'Pomm/Object/BaseObjectMap.class.php',
        'Pomm/Pomm.class.php',
        'Pomm/Connection/Database.class.php', 
        'Pomm/Connection/Transaction.class.php', 
        'Pomm/Connection/Connection.class.php', 
        'Pomm/External/sfInflector.class.php',
        'Pomm/External/sfToolkit.class.php',
        'Pomm/Tools/ParameterHolder.class.php',
        'Pomm/Tools/BaseTool.class.php',
        'Pomm/Tools/CreateBaseMapTool.class.php',
);
    $class_name = array_pop(preg_split('/\\\/', $name));

    foreach ($libs as $lib)
    {
        if (strstr($lib, sprintf('/%s.class.php', $class_name)))
        {
            printf("Loading class '%s' in '%s'\n", $class_name, $lib);
            include(__DIR__."/../".$lib);
            return;
        }
    }
}

