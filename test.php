<?php

function __autoload($name) 
{
    $libs = array(
        'lib/Query/PommWhere.class.php',
        'lib/Exceptions/PommSqlException.class.php',
        'lib/Exceptions/PommException.class.php',
        'lib/Types/PommStrType.class.php',
        'lib/Types/PommBoolType.class.php',
        'lib/Types/PommIntType.class.php',
        'lib/Types/PommTimestampType.class.php',
        'lib/Types/PommHStoreType.class.php',
        'lib/Types/PommBaseType.class.php',
        'lib/Types/PommLTreeType.class.php',
        'lib/Types/PommArrayType.class.php',
        'lib/Objects/PommCollection.class.php',
        'lib/Objects/PommBaseObject.class.php',
        'lib/Objects/PommBaseObjectMap.class.php',
        'lib/Pomm.class.php',
        'lib/Connection/PommDatabase.class.php',
);
    $class_name = array_pop(preg_split('/\\\/', $name));

    foreach ($libs as $lib)
    {
        if (strstr($lib, sprintf('/%s.class.php', $class_name)))
        {
            printf("Loading class '%s' in file '%s'.\n", $name, $lib);
            include($lib);
            return;
        }
    }
}

Pomm\Pomm::createConnection("default", array('dsn' => 'pgsql://greg@localhost/greg'));
