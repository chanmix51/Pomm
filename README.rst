=================================================
POMM: The PHP Object Model Manager for Postgresql
=================================================

What is POMM ?
**************
*Pomm* is a lightweight, fast, efficient and powerful PHP object manager for the Postgresql relational database.

If you do not know *Pomm*, you should have a look on `Pomm's website`_.

_`Pomm's website` http://pomm.coolkeums.org

=====================
How to install Pomm ?
=====================

Download the pomm repository somewhere in your project. All sources are in the Pomm subdirectory. The Pomm class is here to register connections and to give you _Database_ instances. _Database_ instances are here to provide you with connections or transactions. 

::

  pomm
  ├── Pomm
  │   ├── Pomm.php                  # Register and retreive databases
  │   ├── Connection
  │   │   ├── Database.php          # Database provides connections and transactions
  │   │   └── Connection.php        # A connection to the database
  │   ├── Exception
  │   ├── External
  │   ├── Object
  │   │   ├── BaseObjectMap.php     # All your map classes ultimately extend this
  │   │   ├── BaseObject.php        # All your entities extend this
  │   │   └── Collection.php        # Your queries return this
  │   ├── Query
  │   │   └── Where.php             # The Where class
  │   ├── Tools
  │   │   ├── Inspector.php         # Database inspector
  │   │   ├── CreateBaseMapTool.php # Create a Map file from the database
  │   │   └── ScanSchemaTool.php    # Scan a postrgresql's schema to create all map files
  │   └── Type                      # builtin Postgresql types
  ├── sql
  │   └── pomm_lib.sql              # you want this
  └── test
      ├── autoload.php              # a simple autoloader for tests
      └── bootstrap.php             # tests bootstrap

Database schema
***************

With Pomm, the database is the schema. You can use the _Tools_ classes to generate Map files directly. 

You can scan Postgresql's schemas with a short PHP script like the following:

::

    <?php

    require __DIR__.'/vendor/pomm/test/autoload.php';

    $qservice = new Pomm\Service(array('default' => array(
        'dsn' => 'pgsql://nss_user:nss_password@localhost/nss_db'
        )));

    $scan = new Pomm\Tools\ScanSchemaTool(array(
        'dir'=> __DIR__,
        'schema' => 'nss_blog',
        'connection' => $service->getDatabase('default'),
        ));
    $scan->execute();


The script above will generate all the Map files under a Model/Pomm/Entity/NssBlog subdirectory. 
