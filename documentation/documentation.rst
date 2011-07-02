------------------
Pomm Documentation
------------------

Overview
--------
To begin with Pomm it is important to understand some of its parts:

Service 
  This class manages your *Database* connection pool. 
Database
  It can create one or several *Connection* which will allow dialog with the database server and manage transactions. 
Connections
  They will provide you with *Map Classes*. These classes are the central point of your work with *Pomm*. Connections also give you Postgresql's advanced transaction features.
Map Classes
  They are a link between your database and *Entity Classes*. They perform queries that return *Collection* of *Entity Classes* instances. 
Entity Classes
  These instances represent a set of data that can be persisted in the database. These data can be stored under different kind of *Type* which can be associated to a *Converter* to ensure correct representation in PHP.

Directory tree
==============

As Pomm classes' namespace is based on the directory structure, it is important to have a look at the way the model part is organized. A Pomm model directory tree looks like this:

::

  Model
  └── Pomm
      ├── Converter
      ├── Database
      ├── Entity
      │   ├── SchemaA
      │   ├── SchemaB
      │   ├── ...
      │   └── SchemaZ
      │       ├── Base
      │       │   ├── EntityAMap.php
      │       │   ├── EntityBMap.php
      │       │   ├── ...
      │       │   └── EntityZMap.php
      │       ├── EntityAMap.php
      │       ├── EntityA.php
      │       ├── EntityBMap.php
      │       ├── EntityB.php
      │       ├── ...
      │       ├── EntityZMap.php
      │       └── EntityZ.php
      └── Type

Directories:
 * *Converter*           holds the converters for your custom database types
 * *Database*            holds your own *Database* classes if you need any
 * *Entity*              this is where the map classes and their relative entities are stored
 * *Entity/Schema*       schema name will be *Public* by default
 * *Entity/Schema/Base*  where generated files are going to be saved (need write access)
 * *Type*                composite types often need to be stored as class instances in PHP

Dealing with databases
----------------------

Service: the database provider
==============================

The *Service* class just stores your *Database* instances and provide convenient methods to create connections from them. There are several ways to declare databases to the service class. Either you use the constructor passing an array "name" => "connection parameters" or you can use the *setDatabase* method of the service class.::

    # The two examples below are equivalent
    # Using the constructor
    $service = new Pomm\Service(array(
      'db_one' => array(
        'dsn' => 'pgsql://user:pass@host:port/db_a'
      ),
      'db_two' => array(
        'dsn' => 'pgsql://otheruser:hispass@otherhost/db_b',
        'class' => 'App\MyDb'
      )
      ));
    
    # Using the setDatabase method
    $service = new Pomm\Service();
    $service->setDatabase('db_one', new Pomm\Connection\Database(array(
      'dsn' => 'pgsql://user:pass@host:port/db_a'
    )));
    $service->setDatabase('db_two', new App\MyDb(array(
      'dsn' => 'pgsql://otheruser:hispass@otherhost/db_b'
    )));

The *setDatabase* method is used internally by the constructor. The parameters may be any of the following:
 * "dsn": a URL like string to connect the database. It is in the form pgsql://user:password@host:port/database_name (**mandatory**)
 * "class": The *Database* class to instanciate as a database. This class must extend Pomm\\Database as we will see below.
 * "isolation": transaction isolation level. Must be one of Pomm\\Connection\\Connection::ISOLATION_READ_COMMITTED or ISOLATION_SERIALIZABLE (default ISOLATION_READ_COMMITTED).

Once registered, you can retrieve the databases with their name by calling the *getDatabase* method passing the name as argument. If no name is given, the first declared *Database* will be returned.

The **dsn** parameter format is important because it interacts with the server's access policy.

 * *pgsql://user/database* Connect *user* to the db *database* without password through the Unix socket system. This is the minimal form.
 * *pgsql://user:pass/database* The same but with password.
 * *pgsql://user@host/database* Connect *user* to the db *database* on host *host* using TCP/IP.
 * *pgsql://user:pass@host:port/database* The same but with password and TCP port specified. This is the maximal form.

Database and converters
=======================

The *Database* class brings access to mechanisms to create connections and transactions and also register converters. A *Converter* is a class that translates a data type from Postgresql to PHP and from PHP to Postgresql. By default, the following converters are registered, this means you can use them without configuring anything:
 * Boolean: convert postgresql 't' and 'f' to PHP boolean value
 * Number: convert postgresql 'smallint', 'bigint', 'integer', 'decimal', 'numeric', 'real', 'double precision', 'serial', 'bigserial' types to numbers
 * String: convert postgresql 'varchar' and 'text' into PHP string
 * Timestamp: convert postgresql 'timestamp', 'date', 'time' to PHP DateTime instance.

Other types are natively available in postgresql databases but are not loaded automatically by Pomm:

 * Point: convert a postgresql 'point' into a Pomm\\Type\\Point instance.

Postgresql contribs come with handy extra data type (like HStore, a key => value array and LTree a materialized path data type). If you use these types in your database you have to register the according converters from your database instance::

  # The HStore converter converts a postgresql HStore to a PHP associative array and the other way around.
  # The following line registers the HStore converter to the default database.
  
  $service->getDatabase()
    ->registerConverter('HStore', new Pomm\Converter\PgHStore(), array('hstore'));

Arguments for instanciating a *Converter* are the following:
 * the first argument is the converter name. It is used in the *Map Classes* to link with fields (see Map Classes below).
 * the second argument is the instance of the *Converter*
 * the third argument is a word or a set of words for Pomm to identify what converter to use when scanning the database to create the Map files. These words are going to be used in a regular expression match.

You can write your own converters for your custom postgresql types. All they have to do is to implement the *Pomm\\Converter\\ConverterInterface*. This interface makes your converter to have two methods:
 * *fromPg*: convert data from Postgesql by returning the according PHP structure. This data will be implemented as returned here in your entities.
 * *toPg*: return a string with the Postgresql representation of a PHP structure. This string will be used in the SQL queries generated by the Map files to save or update entities.

If your database has a lot of custom types, it is a better idea to create your own *Database* class.::

  class MyDatabase extends Pomm\Connection\Database
  {
    protected function initialize()
    {
      parent::initialize();
      $this->registerConverter('HStore', new Pomm\Converter\Hstore(), array('hstore'));
    }
  }

This way, converters will be automatically registered at instantiation.

Entity converter
================

A nice feature of postgresql when you create a table is a type with the same name as the table is created according to the table structure. Hence, it is possible to use that data type in other tables. Pomm proposes a special converter to do so: the *PgEntity* converter. Passing the table data type name and the associated entity class name will grant you with embedded entities.

::

  class MyDatabase extends Pomm\Connection\Database
  {
    protected function initialize()
    {
      parent::initialize();
      $this->registerConverter('MyEntity', new Pomm\Converter\PgEntity($this, 'Model\Pomm\Entity\Schema\MyEntity'), array('my_entity'));
    }
  }

Converters and types
====================

Composite types are particularly useful to store complex set of data. In fact, with Postgresql, defining a table automatically defines the according type. Hydrating type instances with postgresql values are the work of your custom converters. Let's take an example: electrical transformers windings. A transformer winding is defined by the voltage it is supposed to have and the maximum current it can stands. A transformer have two or more windings so if we define a type WindingPower we will be able to store an array of windings in our transformer table:

::

  -- SQL
  CREATE TYPE winding_power AS (
      voltage numeric(4,1),
      current numeric(5,3)
  );

Tables containing a field with this type will return a tuple. A good way to manipulate that kind of data would be to create a *WindingPower* type class::

  <?php
  
  namespace Model\Pomm\Type;
   
  class WindingPower
  {
      public $voltage;
      public $current;
   
      public function __construct($voltage, $current)
      {
          $this->voltage = $voltage;
          $this->current = $current;
      }
   
      public getPowerMax()
      {
        return $this->voltage * $this->current;
      }
  }

Here, we can see the very good side of this method: we can implement a *getPowerMax()* method and make our type richer. The last thing is we need a converter to translate between PHP and Postgresql::

  <?php
  
  namespace Model\Pomm\Converter;
   
  use Pomm\Converter\ConverterInterface;
  use Model\Pomm\Type\WindingPower as WindingPowerType;
   
  class WindingPower implements ConverterInterface
  {
      protected $class_name;

      public function __contruct($class_name = 'Model\\Pomm\\Type\\WindingPowerType')
      {
          $this->class_name = $class_name;
      }

      public function fromPg($data)
      {
          $data = trim($data, "()");
          $values = preg_split('/,/', $data);
   
          return new $this->class_name($values[0], $values[1]);
      }
   
      public function toPg($data)
      {
          return sprintf("(%4.1f,%4.3f)", $data->voltage, $data->current);
      }
  }

Of course you can hardcode the class to be returned by the converter but it prevents others to extends your type.

Map classes
-----------

Overview
========

Map classes are the central point of Pomm because 
 * they are a bridge between the database and your entities (Pomm\\Object\\BaseObjectMap)
 * they own the structure of the entities (BaseYourEntityMap)
 * They act as Entity provider (YourEntityMap)

Every action you will perform with your entities will use a Map class. They are roughly the equivalent of Propel's *Peer* classes. Although it might look like Propel, it is important to understand unlike the normal Active Record design pattern, entities do not even know how to save themselves. You have to use their relative Map class to save them.

Map classes represent a structure in the database and provide methods to retrieve and save data with this structure. To be short, one table or view <=> one map class.

To be able to be the bridge between your database and your entities, all Map classes **must** extends *Pomm\\Object\\BaseObjectMap* class. This class implements methods that directly interact with the database using the PDO layer. These methods will be explained in the chapter how to query the database.

The structure of the map classes can be automatically guessed from the database hence it is possible to generate the structure part of the map files from the command line (see below). If these classes can be generated, it is advisable not to modify them by hand because modifications would be lost at the next generation. This is why Map classes are split using inheritance:
 * *BaseYourEntityMap* which are abstract classes inheriting from *BaseObjectMap*
 * *YourEntityMap* inheriting BaseYourEntityMap*

*BaseYourEntityMap* is the generated Map file containing the structure for *YourEntity* and *YourEntityMap* is the file where will be your custom entity provider methods.

Structure
=========

When Map classes are instantiated, the method *initialize* is called. This method is responsible of setting various structural elements:
 * *object_name*: the related table name
 * *object_class*: the related entity's fully qualified class name
 * *field_structure*: the fields with the corresponding converters
 * *primary_key*: simple or composite primary key

If the table is stored in a special database schema, it must appear in the *object_name* attribute. If you do not use schemas, postgresql will store everything in the *public* schema. You do not have to specify it in the *object_name* attribute but it will be used in the class namespace.

Let's say we have the following table *student* in the database:

  +-------------+-----------------------------+
  |   Column    |            Type             |
  +=============+=============================+
  |  reference  | character(10)               |
  +-------------+-----------------------------+
  |  first_name | character varying           |
  +-------------+-----------------------------+
  |  last_name  | character varying           |
  +-------------+-----------------------------+
  |  birthdate  | timestamp without time zone |
  +-------------+-----------------------------+
  |  level      | smallint                    |
  +-------------+-----------------------------+

The according generated structure will be:::

 <?php
  namespace Model\Pomm\Entity\Public\Base;

  use Pomm\Object\BaseObjectMap;
  use Pomm\Exception\Exception;

  abstract class BaseStudentMap extends BaseObjectMap
  {
      public function initialize()
      {
          $this->object_class =  'Model\Pomm\Entity\Public\Student';
          $this->object_name  =  'student';
  
          $this->addField('reference', 'String');
          $this->addField('first_name', 'String');
          $this->addField('last_name', 'String');
          $this->addField('birthdate', 'Timestamp');
          $this->addField('level', 'Number');
  
          $this->pk_fields = array('reference');
      }
  }

If the previous table were in the *school* database schema, the following lines would change:::


 <?php
  namespace Model\Pomm\Entity\School\Base;
  ...
          $this->object_class =  'Model\Pomm\Entity\School\Student';
          $this->object_name  =  'school.student';
  

Querying the database
---------------------

Create finders
==============

The first time you generate the *BaseMap* classes, it will also generate the map classes and the entity classes. Using the example with student, the empty map file should look like this::

  <?php
  namespace Model\Pomm\Entity\School;

  use Model\Pomm\Entity\School\Base\StudentMap as BaseStudentMap;
  use Pomm\Exception\Exception;

  class StudentMap extends BaseStudentMap
  {
  }

This is the place you are going to create your own finder methods in. As it extends *BaseObjectMap* via *BaseStudentMap* it already has some useful finders:

 * *findAll()* return all entities
 * *findByPK()* return a single entity

These finders work whatever your entities are. In this class we can declare finders more specific.

Conditions: the Where clause
============================

The simplest way to create a finder with Pomm is to use the *BaseObjectMap*'s method *findWhere()*:

findWhere($where, $values, $suffix)
  return a set of entities based on the given where clause. This clause can be a string or a *Where* instance.

It is possible to use it directly because we are in a Map class hence Pomm knows what table and fields to use in the query.

::

  /* SELECT 
       reference, 
       first_name, 
       last_name, 
       birthdate 
     FROM 
       shool.student 
     WHERE 
         birthdate > '1980-01-01'
  */
  $students = $this->findWhere("birthdate > '1980-01-01'"); 
  
  

Of course, this is not very useful, a finder *getYoungerThan* would be::

  public function getYoungerThan(DateTime $date)
  {
  /* SELECT 
       reference, 
       first_name, 
       last_name, 
       birthdate 
     FROM 
       shool.student 
     WHERE 
         birthdate > '1980-01-01'
     ORDER BY 
       birthdate DESC
     LIMIT 10
  */

    return $this->findWhere("birthdate > ?", array($date->format('Y-m-d')), 'ORDER BY birthdate DESC LIMIT 10');
  }

All queries are prepared, this might increase the performance but it certainly increases the security. The argument here will automatically be escaped by the database and ovoid SQL-injection attacks. If a suffix is passed, it is appended to the query **as is**. The suffix is intended to allow developpers to specify sorting a subset parameters to the query. As the query is prepared, a multiple query injection type attack is not directly possible but be careful if you pass values sent by the customer.

Sometimes, you do not know in advance what will be the clause of your query because it depends on other factors. You can use the *Where* class to do so and chain logical statements.

::

  public function getYoungerThan(DateTime $date, $level = 0)
  {
    $where = new Pomm\Query\Where("birthdate > ?", array($date->format('Y-m-d')));
    $where->andWhere('level >= ?', array($level));

    return $this->findWhere($where, null, 'ORDER BY birthdate DESC LIMIT 10');
  }

The *Where* class has two very handy methods: *andWhere* and *orWhere* which can take string or another *Where* instance as argument. All methods return a *Where* instance so it is possible to chain the calls. The example above can be rewritten this way::

  public function getYoungerThan(DateTime $date, $level = 0)
  {
    $where = Pomm\Query\Where::create("birthdate > ?", array($date->format('Y-m-d')))
      ->andWhere('level >= ?', array($level));

    return $this->findWhere($where, null, 'ORDER BY birthdate DESC LIMIT 10');
  }

Because the WHERE ... IN clause needs to declare as many '?' as given parameters, it has its own constructor:

::

    // SELECT all_fields FROM some_table WHERE station_id IN ( list of ids );
    
    $this->findWhere(Pomm\Query\Where::createIn("station_id", $array_of_ids))

Custom queries
==============

Although it is possible to write whole plain queries by hand in the finders, this may induce coupling between your classes and the database structure. To ovoid coupling, the Map class owns the following methods: *getSelectFields*, *getGroupByFields* and *getFields*.

::

  // Model\Pomm\Entity\Blog\PostMap Class
  public function getBlogPostsWithCommentCount(Pomm\Query\Where $where)
  {
    $sql = sprintf('SELECT %s, COUNT(c.id) as "comment_count" FROM %s p JOIN %s c ON p.id = c.post_id WHERE %s GROUP BY %s',
        join(', ', $this->getSelectFields('p')),
        $this->getTableName('p'),
        $this->Connection->getMapFor('Model\Pomm\Entity\Blog\Comment')->getTableName(),
        $where,
        join(', ', $this->getGroupByFields('p'))
        );

    return $this->query($sql, $where->getValues());
  }

The *query* method is available for your custom queries. It takes 2 parameters, the SQL statement and an optional array of values to be escaped. Keep in mind, the number of values must match the '?' Occurrences in the query.

Whatever you are retrieving, Pomm will hydrate objects according to what is in *$this->object_class* of your map class. The entity instances returned here will have this extra field "comment_count" exactly as it would be a normal field. You can use a *Where* instance everywhere as their *toString* method returns the condition as a string and the *getValues* method return the array with the values to be escaped.

Collections
===========

The *query* method return a *Collection* instance that holds all the *Entity* instances. This collection implements *ArrayAccess* to behave like an Array. Collections have handful methods like:
 * *isFirst()*
 * *isLast()*
 * *isEmpty()*
 * *isOdd()*
 * *isEven()*
 * *getOddEven()*

Entities
--------

Accessors
=========

Internally, all values are stored in an array. The methods *set()* and *get()* are the interface to this array::

  $entity = $map->createObject()
  $entity->has('pika'); // false
  $entity->set('pika', 'chu');
  $entity->has('pika'); // true
  $entity->get('pika'); // chu

*BaseObject* uses magic getters and setters to dynamically build the according methods. The example below is equivalent::

  $entity = $map->createObject()
  $entity->has('pika'); // false
  $entity->setPika('chu');
  $entity->has('pika'); // true
  $entity->getPika()    // chu

This allow developers to overload accessors. The methods *set* and *get* are only used within the class definition and should not be used outside unless you want to bypass any overload that could exist.

Entities implement PHP's *ArrayAccess* interface to use the accessors if any. This means you can have easy access to your entity's data in your templates without bypassing accessors !

::

  $entity['pika'];    // chu
  $entity->getPika(); // chu

Of course you can extend your entities providing new accessors. If by example you have an entity with a weight in grams and you would like to have an accessor that return it in ounces::

  public function getWeightInOunce()
  {
    return round($this->getWeight() * 0.0352739619, 2);
  }

In your templates, you can directly benefit from this accessor while using the entity as an array::

  // in PHP
  <?php echo $thing['weight_in_ounce'] ?>

  // with Twig
  {{ thing.weight_in_ounce }}


Life cycle
==========

Entities are the end of the process, they are the data. Unlike Active Record where entities know how to manage themselves, with Pomm, entities are just data container that may embed processes. Nevertheless, these data container must be formatted to know about their structure and state. This is why entities all inherit from *BaseObject* class and cannot be instantiated directly.

::

  $entity = $map->createObject();
  $entity->isNew();           // true
  $entity->isModified();      // false
  $entity->setPika('chu');
  $entity->isNew();           // true
  $entity->isModified();      // true

  $map->saveOne($entity);     // INSERT

  $entity->isNew();           // false
  $entity->isModified();      // false
  $entity->setPika('no');
  $entity->setPlop(true);
  $entity->isNew();           // false
  $entity->isModified();      // true

  $map->saveOne($entity);     // UPDATE

  $entity->isNew();           // false
  $entity->isModified();      // false
  $entity->setPika('chu');
  $entity->setPlop(false);

  $map->updateOne($entity, array('pika'));

  $map->getPika();            // chu
  $map->getPlop();            // true

  $map->deleteOne($entity);

  $entity->isNew();           // false
  $entity->isModify();        // false

In the example above, you can see there are several ways to save data to the database. The first obvious one is *saveOne()*. Depending on the entity's status is performs an insert or an update on the right table. In the case the entity already exists, all the fields are systematically updated which can sometimes be a problem. If you wish to specifically tell Pomm to update only a subset of the entity, the *updateOne()* method is made for that. This method will save the data you want and will reload the object to reflect eventual changes triggered by the update. This means all other changes are discarded and replaced by the values from the database.

Hydrate and convert
===================

It may happen you need to create objects with data as array. *Pomm* uses this mechanism internally to hydrate the entities with database values. The *hydrate()* method takes an array and merge it with the entity's internal values. Be aware PHP associative arrays keys are case sensitive where postgresql's field names are not. If you need some sort of conversion the *convert()* method will help. You can overload the *convert()* method to create a more specific conversion (if you use web services data provider by example) but you cannot overload the *hydrate()* method. 

Connections
-----------

Map Instance provider
=====================

As soon as you have a database instance, you can create new connections. This is done by using the *createConnection* method. Connections are the way to
 * Retrieve *Map Classes* instances
 * Manage transactions

The entities are stored in a particular database. This is why only connections to this base are able to give you associated Map classes::

  $map = $service->createConnection()
    ->getMapFor('Model\Pomm\Entity\School\Student'); 
  

Transactions
============

By default, connections are in auto-commit mode which means every change in the database is commited on the fly. Connections offer the way to enter in a transaction mode::

  $cnx = $service->getDatabase()
    ->createConnection();
  $cnx->begin();
  try {
    # do things here
    $cnx->commit();
  } catch (Pomm\Exception\Exception $e) {
    $cnx->rollback();
  }

If you need partial rollback, you can use savepoints in your transactions.

::

  $cnx->begin();
  try {
    # do things here
  } catch (Pomm\Exception\Exception $e) {
    // The whole transaction is rolled back
    $cnx->rollback(); 
    exit;
  }
  $cnx->setSavepoint('A');
  try {
    # do other things
  } catch (Pomm\Exception\Exception $e) {
  // only statments after savepoint A are rolled back
    $cnx->rollback('A'); 
  }
  $cnx->commit();

Sometimes, in your model you need some queries to be performed in a transaction without knowing if you are already in a transaction. 

::

    public function doThings()
    {
        if ($this->isInTransaction())
        {
            $savepoint = 'plop';
            $this->setSavepoint($savepoint);
        }
        else
        {
            $savepoint = null;
            $this->begin();
        }

        try
        {
            // do things
            is_null($savepoint) && $this->commit();
        }
        catch (Exception $e)
        {
            $this->rollback($savepoint);
        }
    }

Tools
-----

PHP tools
=========

Pomm comes with *Tools* classes to assist the user in some common tasks. The most used tool is the *BaseMap* classes generation from database inspection. Here is a way you can use this tool to generate all the model files based on the database structure::

  <?php

  require __DIR__.'/vendor/pomm/test/autoload.php';

  $service = new Pomm\Service(array(
      'default' => array(
          'dsn' => 'pgsql://nss_user:nss_password@localhost/nss_db'
  )));

  $scan = new Pomm\Tools\ScanSchemaTool(array(
      'dir'=> __DIR__,
      'schema' => 'transfo',
      'connection' => $service->getDatabase(),
  ));

  $scan->execute();

This will parse the postgresql's schema named *transfo* to scan it for tables and views. Then it will generate automatically the *BaseMap* files with the class structure and if map files or entity files do not exist, will create them. 

Database tools
==============

Pomm comes with a handy set of SQL tools. These functions are coded with PlPgsql so need that language to be created in the database. 

is_email(varchar)
  This function returns true if the parameter is a valid email and false otherwise
is_url(varchar)
  This function returns true if the parameter is a valid url and false otherwise
transliterate(varchar)
  This function replace all accentuated characters by non accentuated Latin equivalent.
slugify(varchar)
  This returns the given string but transliterated, lowered, and all non alphanumerical characters replaced by a dash. This is useful to create meaningful urls.
cut_nicely(varchar, length)
  This function cut a string after a certain length but only on non alphanumerical characters not to cut words.
array_merge(anyelement[], anyelement[])
  Return the merge of both arrays but similar values are present only once in the result.
update_updated_at
  This is for triggers to keep the *updated_at* fields updated.

