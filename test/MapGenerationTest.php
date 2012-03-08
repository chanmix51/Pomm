<?php

namespace Pomm\Test;

use Pomm\Service;
use Pomm\Tools\CreateBaseMapTool;
use Pomm\External\sfInflector;

if (!isset($service))
{
    $service = require __DIR__."/init/bootstrap.php";
}

class MapGenerationTest extends \lime_test
{
    protected $service;
    protected $database;
    protected $map;

    public function initialize(Service $service)
    {
        $this->service = $service;
        $this->database = $this->service
            ->getDatabase();
        $this->map = $this->database
            ->createConnection()
            ->getMapFor('Pomm\Test\TestTable');
        $this->map->createTable();

        return $this;
    }

    public function __destruct()
    {
        parent::__destruct();
        exec(sprintf("rm -r %s", $this->getTmpDir()));
    }

    protected function getTmpDir()
    {
        return sys_get_temp_dir().'/model';
    }

    public function testMapGeneration($hash)
    {
        $tool = new CreateBaseMapTool(array('table' => 'book', 'prefix_dir' => sys_get_temp_dir().'/model', 'database' => $this->database, 'schema' => 'pomm_test'));
        $tool->execute();

        $file = sprintf("%s/%s/PommTest/Base/BookMap.php", $this->getTmpDir(), sfInflector::camelize($this->database->getName()));
        $this->is(md5(file_get_contents($file)), $hash, sprintf("Generated file '%s' match hash '%s'.", $file, $hash));

        return $this;
    }
}

$test = new MapGenerationTest();
$test->initialize($service)
    ->testMapGeneration('1a84678f6b2b569d5812c058c29ab74d');
