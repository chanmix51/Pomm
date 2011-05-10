=========================================
POMM: The Postgresql Object Model Manager
=========================================

What is POMM ?
**************
Pomm is a lightweight, fast, efficient and powerful PHP object manager for the Postgresql relational database.

There are already a lot of ORMs in the PHP world, what makes Pomm different ?
*******************************************************************************
Most of ORMs I know (mostly Doctrine and Propel), work with database abstraction and therefor only propose a common subset of functionalities shared by all database systems (or only some MySQL's features). The query language they propose is poor compared to SQL and is often a limitation that makes the PHP code slower and more complexe. 

Pomm works with Postgresql to benefit from its speed and its rich features set. It uses SQL in the model classes because it is often what developers end up to do with ORMs and because it is easier to optimise queries directly. This also allows developers to work with user defined types, namespaces, arrays, table inheritance, window functions, cryptography, stored procedures, triggers and all other good things Postgresql proposes.

Postgresql is free software and has been evolving in a direction that makes it one of the best database you could entrust with your data. It is sized for small to huge projects dealing with terabytes of data and its maintainers are maniacs about speed and consistency.

I have heard a lot of people saying everything had to be in the PHP layer instead of being in the database, what do you think ?
*******************************************************************************************************************************
Well, there are 2 reasons to put all the code in the PHP layer, one good and one bad. The good reason is the ease at maintaining the application. PHP code is interpreted on the fly and is easy to change, version, store and ... maintain. The code in the database is running all the time. Changing it is tedious and may be complex. The bad reason is because few web developers know SQL and what it can do. 

Good developers are those who create the data structures to make their code simple. In web development, the data structure is tied with the database, knowing SQL and database features can dramatically help us to make our PHP code simpler. Of course, I am not saying to code everything in the database but there are a lot of business processes that do not change over application's life, I think their places are in the database. It makes the application faster and simpler.

DBAL allows people to publish plugins you can use whatever your database system is, what about Pomm ?
*****************************************************************************************************
In my opinion, compatibility due to database abstraction is a myth. First, today you have Doctrine plugins and Propel plugins, choose your side. Then, there are some easy ways you can write queries that work only with one type of database (GROUP BY, ILIKE etc...). You end up with having access only to plugins that work with your database and your ORM.

Pomm works with a database and a version.

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
  │   │   ├── Connection.php        # A simple database connection
  │   │   └── Transaction.php       # Transaction is an enhanced connection
  │   ├── Exception
  │   ├── External
  │   ├── Object
  │   │   ├── BaseObjectMap.php     # All your map classes ultimately extends this
  │   │   ├── BaseObject.php        # All your entities extends this
  │   │   └── Collection.php        # Your queries return this
  │   ├── Query
  │   │   └── Where.php             # The Where class
  │   ├── Tools
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

    Pomm\Pomm::setDatabase('default', array(
        'dsn' => 'pgsql://nss_user:nss_password@localhost/nss_db'
        ));

    $scan = new Pomm\Tools\ScanSchemaTool(array(
        'dir'=> __DIR__.'/Model/Pomm/Map',
        'schema' => 'nss_blog',
        'connection' => Pomm\Pomm::getDatabase('default'),
        'namespace' => 'Model\Pomm\Map'
        ));
    $scan->execute();


The script above will generate all the Map files under a Model/Pomm/Map subdirectory. 

Configuring Pomm
****************

Here is an example:

::

  <?php

    Pomm::setDatabase('db_alias', array('dsn' => 'pgsql://user:password@host:port/db_name'));

You can register as many databases as you want. Note that all database are aliased but the first database registered will be returned when the _getDatabase_ method is called with no database alias.

Using Pomm
**********

Once your databases are registered, you can use the mapfiles directly from your code:

::

  <?php

    $tr = Pomm::getDatabase()->getTransaction();

    $collection = $tr->getMapFor('Model\Pomm\MyTable')
        ->findAll();

    $tr->begin();
    try {
    // do something here
    } catch (Pomm\Exception\Exception $e) {
      $tr->rollback();
      // jump to somewhere else
    }

    $tr->setSavepoint('plop');  // save point named "plop"
    try {
      // do things there
    } catch (Pomm\Exception\Exception $e) {
      $tr->rollback('plop');   // only the queries since "plop" are rolled back
    }

    $tr->commit();             // Everything is executed in database here





