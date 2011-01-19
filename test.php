<?php

function __autoload($name) 
{
    $libs = array(
        'lib/Query/Where.class.php',
        'lib/Exception/SqlException.class.php',
        'lib/Exception/Exception.class.php',
        'lib/Type/StrType.class.php',
        'lib/Type/BoolType.class.php',
        'lib/Type/IntType.class.php',
        'lib/Type/TimestampType.class.php',
        'lib/Type/HStoreType.class.php',
        'lib/Type/BaseType.class.php',
        'lib/Type/LTreeType.class.php',
        'lib/Type/ArrayType.class.php',
        'lib/Object/Collection.class.php',
        'lib/Object/BaseObject.class.php',
        'lib/Object/BaseObjectMap.class.php',
        'lib/Pomm.class.php',
        'lib/Connection/Database.class.php', 
);
    $class_name = array_pop(preg_split('/\\\/', $name));

    foreach ($libs as $lib)
    {
        if (strstr($lib, sprintf('/%s.class.php', $class_name)))
        {
            printf("Loading class '%s' in '%s'\n", $class_name, $lib);
            include($lib);
            return;
        }
    }
}

Pomm\Pomm::createConnection("local", array('dsn' => 'pgsql://greg/greg'));
Pomm\Pomm::createConnection("host", array('dsn' => 'pgsql://greg@localhost/greg'));

$result = Pomm\Pomm::executeAnonymousQuery('SELECT 4', 'local');
print_r($result->fetch());

try
{
    $result = Pomm\Pomm::executeAnonymousQuery('SELECT 4', 'host');
}
catch(\Pomm\Exception\Exception $e)
{
    printf("Pomm Exception caught with message '%s'\n", $e->getMessage());
}
Pomm\Pomm::createConnection("host", array('dsn' => 'pgsql://test:test0!@localhost/greg'));
$result = Pomm\Pomm::executeAnonymousQuery('SELECT 4<>1', 'host');
print_r($result->fetch());

