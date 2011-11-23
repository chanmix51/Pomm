<?php

function __autoload($name) 
{
    $libs = array(
        'Pomm/Query/Where.php',
        'Pomm/Exception/SqlException.php',
        'Pomm/Exception/Exception.php',
        'Pomm/Object/Collection.php',
        'Pomm/Object/BaseObject.php',
        'Pomm/Object/BaseObjectMap.php',
        'Pomm/Object/Pager.php',
        'Pomm/Service.php',
        'Pomm/Connection/Database.php', 
        'Pomm/Connection/Connection.php', 
        'Pomm/External/sfInflector.php',
        'Pomm/External/sfToolkit.php',
        'Pomm/Tools/ParameterHolder.php',
        'Pomm/Tools/BaseTool.php',
        'Pomm/Tools/CreateBaseMapTool.php',
        'Pomm/Tools/ScanSchemaTool.php',
        'Pomm/Tools/CreateEntityTool.php',
        'Pomm/Tools/CreateMapTool.php',
        'Pomm/Converter/ConverterInterface.php',
        'Pomm/Converter/PgBoolean.php',
        'Pomm/Converter/PgHStore.php',
        'Pomm/Converter/PgNumber.php',
        'Pomm/Converter/PgLTree.php',
        'Pomm/Converter/PgString.php',
        'Pomm/Converter/PgTimestamp.php',
        'Pomm/Converter/PgInterval.php',
        'Pomm/Converter/PgPoint.php',
        'Pomm/Converter/PgEntity.php',
        'Pomm/Converter/PgLseg.php',
        'Pomm/Converter/PgCircle.php',
        'Pomm/Type/Point.php',
        'Pomm/Type/Segment.php',
        'Pomm/Type/Circle.php',
        'Pomm/Identity/IdentityMapper.php',
        'Pomm/Identity/IdentityMapperInterface.php',
);
    $name = preg_split('/\\\/', $name);
    $class_name = array_pop($name);

    foreach ($libs as $lib)
    {
        if (strstr($lib, sprintf('/%s.php', $class_name)))
        {
            printf("Loading class '%s' in '%s'\n", $class_name, $lib);
            require(__DIR__."/../".$lib);

            return;
        }
    }
}

