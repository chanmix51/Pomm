<?php

include(dirname(__FILE__).'/../../../../test/bootstrap/unit.php');
$db_manager = new sfDatabaseManager($configuration);

class TestTableMap extends PgLookBaseObjectMap
{
  protected function initialize()
  {
    $this->connection = PgLook::getConnection();
    $this->object_class = 'TestTable';
    $this->field_definitions = array('something' => 'PgLookStrType');
  }
}

class TestTable extends PgLookBaseObject
{
}

class PgLookTest extends PgLook
{
  protected static $test;
 
  public static function initialize()
  {
    self::$test = new lime_test();
  }

  public static function saveConnectionsTest($db_manager)
  {
    self::saveConnections($db_manager);
    self::$test->ok(count(self::$connections), 'We have connections');
  }

  public static function getConnectionTest()
  {
    $cnx = self::getConnection();
    self::$test->isa_ok($cnx, 'sfPgLookDatabase', 'We have a sfPgLookDatabase');
    $key = array_pop(array_keys(self::$connections));
    $cnx = self::getConnection($key);
    self::$test->isa_ok($cnx, 'sfPgLookDatabase', 'We have a sfPgLookDatabase named '.$key);
  }

  public static function getMapForTest()
  {
    $class = self::getMapFor('TestTable');
    self::$test->isa_ok($class, 'TestTableMap','We get TestTableMap');
  }
 }

PgLookTest::initialize();
PgLookTest::saveConnectionsTest($db_manager);
PgLookTest::getConnectionTest();
PgLookTest::getMapForTest();

