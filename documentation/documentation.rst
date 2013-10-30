==================
Pomm Documentation
==================

.. contents::

********
Overview
********

Pomm is a fast, lightweight, efficient model manager for Postgresql written in PHP. It can be seen as an enhanced object hydrator above PHP's native Postgresql library with the following features:

 * Database Inspector to build automatically your PHP model files (support inheritance).
 * Postgresql's schema support mapped to PHP namespaces.
 * PHP <=> Postgres type converter that support HStore, geometric types, objects, ranges etc.
 * Lazy fetching for results.
 * Hydration filters trough PHP callables.
 * Field selector methods for all SQL queries.
 * Identity mapper with several different algorithms available.

************************
Databases and converters
************************

Service: the database provider
==============================

Database class and configuration
--------------------------------

The ``Service`` class just stores the ``Database`` instances and provides convenient methods to create connections from them. It is mainly intended to be used with dependency injection containers used by some popular frameworks. The Database class has different roles:

 * Connection builder and pool.
 * Converters holder.
 * Configuration holder.

It is either possible to instance `Database` class alone or use the `Service` class to do so. The simplest way to get a database instance is::

    $database = new Pomm\Connection\Database(array('name' => 'database_name', 'dsn' => 'pgsql://user:pass@host:port/db_name'));

Database expected parameters are:

 * dsn (string, mandatory): Connection string (see `DSN`_).
 * name (string, optional, default: physical database name): Logical database name that is used as primary namespace for PHP entity object.
 * configuration (array, optional, see `Connection configuration`_ below): Client configuration for each connection.
 * isolation (string, optional, default: ``ISOLATION_READ_COMMITTED``, see `Standard transaction`_): isolation level used in transactions.
 * identity_mapper (string, optional, default: ``Smart``, see `Identity mappers`_ below): default identity mapper class name for connections.

There are several ways to declare databases to the service class. Either you use the constructor passing an array "name" => "connection parameters" or you can use the ``setDatabase()`` method of the service class.::

    # The two examples below are equivalent
    # Using the constructor
    $service = new Pomm\Service(array(
      'db_one' => array(
        'dsn' => 'pgsql://user:pass@host:port/db_a'
      ),
      'db_two' => array(
        'dsn'   => 'pgsql://otheruser:hispass@!/path/to/socket/directory!/db_b',
        'class' => 'App\MyDb',
        'identity_mapper' => 'App\MyIdentityMapper',
        'name'  => 'my_db'
      )
      ));
    
    # Using the setDatabase method
    $service = new Pomm\Service();
    $service->setDatabase('db_one', new Pomm\Connection\Database(array(
      'dsn' => 'pgsql://user:pass@host:port/db_a'
    )));
    $service->setDatabase('db_two', new App\MyDb(array(
      'dsn' => 'pgsql://otheruser:hispass@!/path/to/socket/directory!/db_b',
      'identity_mapper' => 'App\MyIdentityMapper',
      'name'  => 'my_db'
    )));

The *setDatabase* method is used internally by the constructor. Once registered, you can retrieve the databases with their name by calling the *getDatabase* method passing the name as argument. If no name is given, the first declared *Database* will be returned.

DSN
---

The **dsn** parameter format is important because it interacts with Postgresql server's access policy.

 * **socket connection**
 * ``pgsql://user/database`` Connect *user* to the db *database* without password through the Unix socket system. 
 * ``pgsql://user:pass/database`` The same but with password.
 * ``pgsql://user:pass@!/path/to/socket!/database`` When the socket is not in the default directory, it is possible to specify it in the host part of the DSN. Note it is surrounded by '!' and there are NO ending /. Using the «!» as delimiter assumes there are no «!» in your socket's path. But you don't have «!» in your socket's path do you ?
 * ``pgsql://user@!/path/to/socket!:port/database`` Postgresql's listening socket's names are the same as TCP ports. If different than default socket, specify it in the port part.


 * **TCP connection**
 * ``pgsql://user@host/database`` Connect *user* to the db *database* on host *host* using TCP/IP.
 * ``pgsql://user:pass@host:port/database`` The same but with password and TCP port specified. 

Connection configuration
------------------------

Connections set client parameters at launch (see `documentation <http://www.postgresql.org/docs/9.3/static/runtime-config-client.html>`_). Default parameters are the following
 * bytea_output = escape
 * intervalstyle = ISO_8601
 * datestyle = ISO

These parameters are important since the default converters expect client output to be formatted this way. If you change these parameters, register the according converter. 

Some other parameters can be tuned that way, by default they are set by the server's default configuration:
 * statement_timeout
 * lock_timeout
 * TimeZone
 * extra_float_digits

Converters
==========

Built-in converters
-------------------

The ``Database`` class brings access to mechanisms to create connections and also to register converters. A ``Converter`` is a class that translates a data type between PHP and Postgresql.

By default, the following converters are registered, this means you can use them without configuring anything:
 * ``Boolean``: convert postgresql booleans 't' and 'f' to/from PHP boolean values
 * ``Number``: convert postgresql 'smallint', 'bigint', 'integer', 'decimal', 'numeric', 'real', 'double precision', 'serial', 'bigserial' types to numbers
 * ``String``: convert postgresql 'varchar', 'char', 'bpchar', 'uuid', 'tsvector', 'xml', 'json' (Pg 9.2), 'name' and 'text' into PHP string
 * ``Timestamp``: convert postgresql 'timestamp', 'date', 'time' to PHP ``DateTime`` instance.
 * ``Interval``: convert postgresql's 'interval' type into PHP ``DateInterval`` instance. 
 * ``Binary``: convert postgresql's 'bytea' type into PHP string (see bugs `here <https://github.com/chanmix51/Pomm/issues/31>_` and `here <https://github.com/chanmix51/Pomm/issues/32>_`).
 * ``Array``: convert postgresql arrays from/to PHP arrays.
 * ``TsRange``: convert postgresql 'tsrange', 'daterange' to ``\Pomm\Type\TsRange`` instance (Pg 9.2).
 * ``NumberRange``: convert postgresql 'int4range', 'int8range', 'numrange` into ``\Pomm\Type\NumberRange`` instance (Pg 9.2).

Registering converters
----------------------

Other types are natively available in postgresql databases but are not loaded automatically by Pomm.

 * ``Point``: postgresql 'point' representation as ``Pomm\Type\Point`` instance.
 * ``Segment``: 'segment' representation as ``Pomm\Type\Segment``.
 * ``Circle``: 'circle' representation as ``Pomm\Type\Circle``.

Postgresql contribs come with handy extra data type (like HStore, a key => value array and LTree a materialized path data type). If you use these types in your database you have to **register the according converters** from your database instance::

  # The HStore converter converts a postgresql HStore to a PHP associative 
  # array and the other way around.
  # The following line registers the HStore converter to the default 
  # database.
  
    $database
    ->registerConverter(
      'HStore',
       new Pomm\Converter\PgHStore(),
       array('public.hstore')
      );

Arguments to instantiate a ``Converter`` are the following:
 * the first argument is the converter name. It is used in the map classes to link with fields (see `Map Classes`_ below).
 * the second argument is the instance of the ``Converter``
 * the third argument is a type or a set of types for Pomm to link them with the given converter.

If your database has a lot of custom types, it is a better idea to create your own ``Database`` class.::

  class MyDatabase extends Pomm\Connection\Database
  {
    protected function initialize()
    {
      parent::initialize();

      $this->registerConverter('HStore',
        new Pomm\Converter\Hstore(), array('hstore'));

      $this->registerConverter('Point',
        new Pomm\Converter\Pgpoint(), array('point'));

      $this->registerConverter('Circle',
        new Pomm\Converter\PgCircle(), array('circle'));
    }
  }

This way, converters will be automatically registered at instantiation.

Converters and types
====================

Domains
-------

In case your database uses ``DOMAIN`` types you can associate them with an already registered converter. The ``registerTypeForConverter()`` method stands for that.::

    $database
      ->registerTypeForConverter('email_address', 'String');

In the example above, the database contains a domain ``email_address`` which is a subtype of ``varchar`` so it is associated with the built-in converter ``String``.

**Note** ``registerTypeForConverter`` and ``registerConverter`` methods implement the fluid interface so you can chain calls.

Custom types
------------
Composite types are particularly useful to store complex set of data. In fact, with Postgresql, defining a table automatically defines the corresponding type. Hydrating type instances with postgresql values are the work of your custom converters. Let's take an example: electrical transformers. Electrical transformers are composed by at least two wiring, an input one (named primary) and an output one (named secondary) but it can be more of them. A transformer winding is defined by the voltage it is supposed to have and the maximum current it can stands.   ::

  -- SQL
  CREATE TYPE winding_power AS (
      voltage numeric(4,1),
      current numeric(5,3)
  );

Tables containing a field with this type will return a tuple. A good way to manipulate that kind of data would be to create a ``WindingPower`` type class::

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

Here, we can see the very good side of this method: we can implement a ``getPowerMax()`` method and make our type richer. 

Writing your own converters
---------------------------

You can write your own converters for your custom postgresql types. All they have to do is to implement the ``Pomm\Converter\ConverterInterface``. This interface makes your converter to have two methods:
 * ``fromPg($data, $type)``: converts string data from Postgesql to a PHP representation. The returned value will be hydrated in your entities.
 * ``toPg($data, $type)``: returns a string with the Postgresql representation of a PHP structure. This string will be used in the SQL queries generated by the Map files to save or update entities.

Here is the converter for the ``WindingPower`` type mentioned above::

  <?php
  
  namespace Model\Pomm\Converter;
   
  use Pomm\Converter\ConverterInterface;
  use Model\Pomm\Type\WindingPower as WindingPowerType;
   
  class WindingPower implements ConverterInterface
  {
      public function fromPg($data, $type = null)
      {
          $data = trim($data, "()");
          $values = preg_split('/,/', $data);
   
          return new WindingPowerType($values[0], $values[1]);
      }
   
      public function toPg($data, $type = null)
      {
          return sprintf("winding_power '(%4.1f,%4.3f)'", $data->voltage, $data->current);
      }
  }

It is advised not to hard-code the name of the class type so other developers may extend it and use theirs.

Entity converter
----------------

In Postgresql, creating a table means creating a new type with the table's fields definition. Hence, it is possible to use that data type in other tables or use them as objects in your SQL queries. Pomm proposes a special converter to do so: the ``PgEntity`` converter. Passing the table data type name and the associated entity class name will grant you with embedded entities.

::

    $database
      ->registerConverter('MyEntity', new \Pomm\Converter\PgEntity($my_entity_map), array('my_schema.my_entity));

********
Entities
********

Overview
========

What is an Entity class ?
-------------------------

Entities are what programmers use in the end of the process. They are an object oriented implementation of the data retrieved from the database. Most of the time, these PHP classes are automatically generated by the introspection tool (see `CreateBaseMapTool`_) but you can write you own classes by hand. They just have to extends ``Pomm\Object\BaseObject`` class to know about status (see `Life cycle`_). Important things to know about entities are **they are schema less** and **they are data source agnostic**. 

By default, entities lie in the same directory than their map classes and de facto share the same namespace but this is only convention.

::

    <?php

    namespace Database\Schema;

    use Pomm\Object\BaseObject;
    use Pomm\Exception\Exception;

    class MyEntity extends BaseObject
    {
    }


Data source agnostic
--------------------

Entities do not know anything about database in general. This means they do not know how to save, retrieve or update themselves (see `Map classes`_ for that). You can use ``BaseObject`` children to store data of your web services, NoSQL database etc. They use the ``hydrate()`` method to get data and accessors to read / write data from them (see `Living with entities`_ below).

Schema less entities
--------------------

Entities do not know anything about the structure of the tables, views etc. They are just flexible typed containers for data. They use PHP magic methods to simulate getters and setters on data they own (see `Living with entities`_ below). This is very powerful because you can access entities like they were arrays and benefit from method overloads.

..

    Note that entities do not know anything about their primary key either.

Living with entities
====================

Creator
-------

There are several ways to create entities. Use the constructor or use the creator methods from its related map class (see `Map classes`_).

::

  $entity = new Database\Schema\MyEntity();

  $entity = $database
    ->createConnection()
    ->getMapFor('Database\Schema\MyEntity')
    ->createObject();

These methods accept an optional array of values. If provided, values will hydrate the entity.

::

  $entity = $database
    ->createConnection()
    ->getMapFor('Database\Schema\MyEntity')
    ->createAndSaveObject(array('name' => 'pika', 'age' => 23, 'stamped_at' => new \DateTime()));


Accessors and mutators
----------------------
The abstract parent ``BaseObject`` uses magic getters and setters to dynamically build the according methods. Internally, all values are stored in an array. The methods ``set()`` and ``get()`` are the interface to this array::

  $entity = new Database\Schema\MyEntity();
  $entity->has('pika'); // false
  $entity->set('pika', 'chu');
  $entity->has('pika'); // true
  $entity->get('pika'); // chu
  $entity->clear('pika');
  $entity->has('pika'); // false

Note that ``get()`` can take an array with multiple attributes::

  $entity->set('pika', 'chu');
  $entity->set('plop', true);

  $entity->get(array('pika', 'plop')); // returns array('pika' => 'chu', 'plop' => true);
  $entity->get($map->getPrimaryKey()); // returns the primary key if set.


``get()``, ``clear()`` and ``set()`` are **generic accessors**. They are used internally and cannot be overloaded. But you can also use **virtual accessors**::

    $entity = new Database\Schema\MyEntity(array('pika' => 'chu'));
    $entity->getPika();      // chu

They are called virtual because they do not exist by default but ``BaseObject`` implements the ``__call()`` method to trap accessors calls using the ``get()`` and ``set()`` generic methods. Of course all these can be overloaded::

  // in the Entity class
  public function getPika()
  {
    return strtoupper($this->get('pika'));
  }
    
  // elsewhere
  $entity = new Database\Schema\MyEntity(array('pika' => 'chu'));
  $entity->getPika();     // CHU

The methods ``set()`` and ``get()`` should be used only if you want to bypass any overload that could exist.

Interfaces and overloads
------------------------
Entities implement PHP's ``ArrayAccess`` interface to use the accessors if any. This means you can have easy access to your entity's data in your templates without bypassing accessors !

::

  // in the Entity class
  public function getPika()
  {
    return strtoupper($this->get('pika'));
  }

  // elsewhere
  $entity->setPika('chu');
  $entity->getPika();     // CHU
  $entity['pika'];        // CHU
  $entity->pika;          // CHU
  
  $entity->get('pika');   // chu

This also applies to ``set()`` and ``clear()`` methods.

Extending entities
------------------

Of course you can extend your entities providing new accessors. If by example you have an entity with a weight in grams and you would like to have a getter that returns it in ounces::

  public function getWeightInOunce()
  {
    return round($this->getWeight() * 0.0352739619, 2);
  }

In your templates, you can directly benefit from this getter while using the entity as an array::

  // in PHP
  <?php echo $thing['weight_in_ounce'] ?>

  // with Twig
  {{ thing.weight_in_ounce }}

Entities and database
=====================

Hydrate and convert
-------------------

It may happen you need to create objects with data as array. ``Pomm`` uses this mechanism internally to hydrate the entities with database values. The ``hydrate()`` method takes an array and merge it with the entity's internal values. Be aware PHP associative arrays keys are case sensitive while postgresql's field names are not. If you need some sort of conversion the ``convert()`` method will help. You can overload the ``convert()`` method to create a more specific conversion (if you use web services data provider by example) but you cannot overload the ``hydrate()`` method. 

Life cycle
----------

Entities also propose mechanisms to check what state are their data compared to the data source. There are 2 states which present 4 possible combinations:

**EXIST**
  The instance is fetched from the data source.
**MODIFIED**
  This instance has been modified with mutators.

So, of course, an entity can be in both states EXIST and MODIFIED or NONE of them. The ``BaseObject`` class grants you with several methods to check this internal state: ``isNew()``, ``isModified()`` or you can directly access the ``_state`` attribute from within your class definition::

  $entity = $map->createObject();
  $entity->isNew();           // true
  $entity->isModified();      // false
  $entity->setPika('chu');
  $entity->isNew();           // true
  $entity->isModified();      // true

***********
Map classes
***********


Overview
========

Map classes are the central point of Pomm because 
 * they are a bridge between the database and your entities
 * they own the structure of their corresponding entities 
 * They act as entity providers

Every action you will perform with your entities will use a Map class. They are roughly the equivalent of Propel's *Peer* classes. Although it might look like Propel, it is important to understand unlike the normal Active Record design pattern, entities do not even know their structure and how to save themselves. You have to use their relative Map class to save them.

Map classes represent a structure in the database and provide methods to retrieve and save data with this structure. To be short, one table or view => one map class.

To create the link between your database and your entities, all Map classes **must** at the end extends ``\Pomm\Object\BaseObjectMap``. This class implements methods that directly interact with the database using the PDO layer. These methods will be explained in the chapter `Querying the database`_.

The structure of the map classes can be automatically guessed from the database hence it is possible to generate the structure part of the map files from the command line (see below). If these classes can be generated, it is advisable not to modify them by hand because modifications would be lost at the next generation. This is why Map classes are split using inheritance:
 * ``BaseYourEntityMap`` which are abstract classes inheriting from ``\Pomm\Object\BaseObjectMap``
 * ``YourEntityMap`` inheriting from ``BaseYourEntityMap``.

``BaseYourEntityMap`` can be skipped but since Pomm proposes automatic code generation, this file can be regenerated over and over without you to loose precious custom code. This is why this file owns the data structure read from the database. If you create a map file that does not rely on automatic generation, it has not not to use a BaseMap file.

Structure
=========

Introspected tables
-------------------

When Map classes are instantiated, the method ``initialize`` is triggered. This method is responsible of setting various structural elements:
 * ``object_name``: the related table name
 * ``object_class``: the related entity's fully qualified class name
 * ``field_structure``: the fields with their corresponding type
 * ``primary_key``: an array with simple or composite primary key

If the table is stored in a special database schema, it must appear in the ``object_name`` attribute. If you do not use schemas, postgresql will store everything in the public schema. You do not have to specify it in the ``object_name`` attribute but it will be used in the class namespace. As ``public`` is also a reserved keyword of PHP, the namespace for the public schema is ``PublicSchema``.

Let's say we have the following table ``student`` in the ``public`` schema of the database ``college``::

  +-------------+-------------------------------+
  |   Column    |            Type               |
  +=============+===============================+
  |  reference  | character(10)                 |
  +-------------+-------------------------------+
  |  first_name | character varying             |
  +-------------+-------------------------------+
  |  last_name  | character varying             |
  +-------------+-------------------------------+
  |  birthdate  | timestamp without time zone   |
  +-------------+-------------------------------+
  |  level      | smallint                      |
  +-------------+-------------------------------+
  |  exam_dates | timestamp without time zone[] |
  +-------------+-------------------------------+

The last field ``exam_dates`` is an array of timestamps (see `Arrays`_ below). The corresponding PHP structure will be::

 <?php

  namespace College\PublicSchema\Base;

  use Pomm\Object\BaseObjectMap;
  use Pomm\Exception\Exception;

  abstract class StudentMap extends BaseObjectMap
  {
      public function initialize()
      {
          $this->object_class =  '\College\PublicSchema\Student';
          $this->object_name  =  'student';
  
          $this->addField('reference', 'char');
          $this->addField('first_name', 'varchar');
          $this->addField('last_name', 'varchar');
          $this->addField('birthdate', 'timestamp');
          $this->addField('level', 'smallint');
          $this->addField('exam_dates', 'timestamp[]');
  
          $this->pk_fields = array('reference');
      }
  }

All generated map classes use PHP namespace. This namespace is composed by the database name and the database schema the table is located in. If database name is not supplied to the ``Database`` constructor (see `Database class and isolation level`_), the real database name is used. If by example, the previous table were in the ``school`` database schema, the following lines would change::

 <?php

  namespace College\School\Base;
  ...
          $this->object_class =  'College\School\Student';
          $this->object_name  =  'school.student';
  
Arrays
------

Postgresql supports arrays. An array can contain several entities all from the same type. Pomm of course supports this feature using the ``[]`` notation after the converter declaration::

    $this->addField('authors', 'varchar[]');   // Array of strings
    $this->addField('locations', 'point[]');   // Array of points

The converter system handles that and the entities will be hydrated with an array of the according type depending on the given converter. Of course, all converters must be registered prior to the declaration.

Temporary tables
----------------

Sometimes, you might want to create temporary tables. A map class can create its own table, modify it and destroy it. Let's imagine we have to create a temporary tables for students and their average scores in each discipline. The following map class could do the job::

    <?php

    namespace College\School;

    use Pomm\Object\BaseObjectMap;
    use Pomm\Object\BaseObject;
    use Pomm\Query\Where;

    class AverageStudentScoreMap extends BaseObjectMap
    {
        public function initialize()
        {
          $this->object_class =  'College\School\AverageStudentScore';
          $this->object_name  =  'school.average_student_score';

          $this->addField('reference', 'varchar');
          $this->addField('maths', 'numeric');
          $this->addField('physics', 'numeric');
          ...
        }

        public function createTable()
        {
          $sql = "CREATE TEMPORARY TABLE %s (reference VARCHAR PRIMARY KEY, ...

          $this->query(sprintf($sql, $this->getTableName()), array());
        }

        public function dropTable()
        {
          $sql = "DROP TABLE %s CASCADE";

          $this->query(sprintf($sql, $this->getTableName()), array());
        }
    }

You can create methods to change the table structure, add or drop columns etc. This is what it is done by example in the converter test script.

Querying the database
=====================

Create, update, drop
--------------------

The main goal of the map classes is to provide a layer between your database and your entities. They provide you with basic tools to save, update and delete your entities trough ``saveOne()``, ``updateOne()`` and ``deleteOne()`` methods.

::

  $entity = $map->createObject(array('pika' => 'chu', 'plop' => false));

  $map->saveOne($entity);     // INSERT

  $entity->setPika('no');
  $entity->setPlop(true);

  $map->saveOne($entity);     // UPDATE

As illustrated above, the ``saveOne()`` method saves your object whatever it is an update or an insert. It is important to know that the internal state (see `Life cycle`_) of the entity is used to determine if the object exists or not and choose between the ``INSERT`` or the ``UPDATE`` statement. 
Whatever is used, the whole structure is saved every time this method is called. In case you do just update some fields you can use the ``updateOne()`` method.
Note that if the table related to this entity sets default values (like ``created_at`` field by example) they will be **automatically hydrated in the entity**.

::

  $entity->setPika('chu');
  $entity->setPlop(false);

  $map->updateOne($entity, array('pika')); // UPDATE ... set pika='...'

  $map->getPika();            // chu
  $map->getPlop();            // true

In the example above, two fields are set and only one is updated. The result of this is the second field to be **replaced with the value from the database**. 

::

  $map->deleteOne($entity);

  $entity->isNew();           // false
  $entity->isModified();        // false

The ``deleteOne()`` method is pretty straightforward. Like the other modifiers, it hydrates the object with the deleted row from the database in case you want to save it elsewhere.

Built-in finders
----------------

The first time you generate the base map classes, it will also generate the map classes and the entity classes. Using the example with student, the empty map file should look like this::

  <?php
  namespace College\School;

  use College\School\Base\StudentMap as BaseStudentMap;
  use Pomm\Exception\Exception;
  use Pomm\Query\Where;
  use College\School\Student;

  class StudentMap extends BaseStudentMap
  {
  }

This is the place you are going to create your own finder methods. As it extends ``BaseObjectMap`` via ``BaseStudentMap`` it already has some useful finders:

 * ``findAll(...)`` return all entities
 * ``findByPK(...)`` return a single entity
 * ``findWhere(...)`` perform a 
   ``SELECT ... FROM my.table WHERE ...``

Finders return either a ``Collection`` instance virtually containing all model instances returned by the query (see `Collections`_) or just a related model entity instance (like ``findByPK``).

findAll
-------

``findAll`` is the simplest query you can make on a database set, it returns all the tuples of the set. This method takes a query suffix as optional argument. This is useful for query modifiers like ``LIMIT ... OFFSET`` or ``ORDER BY``.

::

  $map->findAll('ORDER BY created_at DESC LIMIT 5');

  // corresponding query
  SELECT
    "field1" AS "field1",
    ...
  FROM
    table_name
  ORDER BY created_at DESC LIMIT 5

**note** If you are just interested by the suffix to paginate your queries, have a look at `Pagers_`.

findWhere
---------

The simplest way to create a query with Pomm is to use the ``findWhere()`` method.

findWhere($where, $values, $suffix)
  returns a set of entities based on the given where clause. This clause can be a string or a ``Where`` instance.

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
         birthdate > '1980-01-01 
       AND 
         first_name ILIKE '%an%'
  */

  // don't do that !
  $students = $this->findWhere("birthdate > '1980-01-01' AND first_name ILIKE '%an%'"); 
  

Of course, this is not very useful, because the date is very likely to be a parameter. A finder ``getYoungerThan`` would be::

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
         birthdate > $date
       AND 
         first_name ILIKE '%an%'
     ORDER BY 
       birthdate DESC
     LIMIT 10
  */

    return $this->findWhere("birthdate > ? AND first_name ILIKE ?", 
        array($date, '%an%'), 
        'ORDER BY birthdate DESC LIMIT 10'
        );
  }

All queries are prepared, this might increase the performance but it certainly increases the security. Passing the argument using the question mark makes it automatically to be escaped by the database and avoid SQL-injection attacks. If a suffix is passed, it is appended to the query **as is**. The suffix is intended to allow developers specifying the sorting order of a subset. As the query is prepared, a multiple query injection type attack is not directly possible but be careful if you pass directly values sent by untrusted source.

**Note** The DateTime PHP instances can be passed as is, they will be converted into string internally.

AND OR: The Where class
-----------------------

Sometimes, you do not know in advance what will be the clauses of your query because it depends on variable factors. You can use the ``Where`` class to chain logical statements::

  public function getYoungerThan(DateTime $date, $needle)
  {
    $where = new Pomm\Query\Where("birthdate > ?", array($date));
    $where->andWhere('first_name ILIKE ?', array(sprintf('%%%s%%', $needle)));

    return $this->findWhere($where, null, 'ORDER BY birthdate DESC LIMIT 10');
  }

The ``Where`` class has two very handy methods: ``andWhere`` and ``orWhere`` which can take string or another ``Where`` instance as argument. All methods return a ``Where`` instance so it is possible to chain the calls. The example above can be rewritten this way::

  public function getYoungerThan(DateTime $date, $needle)
  {
    $where = Pomm\Query\Where::create("birthdate > ?", array($date))
        ->andWhere('first_name ILIKE ?', array(sprintf('%%%s%%', $needle)))

    return $this->findWhere($where, null, 'ORDER BY birthdate DESC LIMIT 10');
  }

Because the ``WHERE something IN (...)`` clause needs to declare as many '?' as given parameters, it has its own constructor::

    // WHERE (station_id, line_no) IN ((1, 1), (1, 3), ... );

    $this->findWhere(Pomm\Query\Where::createWhereIn("(station_id, line_no)", array(array(1, 1), array(1, 3)))

The ``Where`` instances can be combined together with respect of the logical precedence::

    $where1 = new Pomm\Query\Where('pika = ?', array('chu'));
    $where2 = new Pomm\Query\Where('age < ?', array(18));

    $where1->orWhere($where2);
    $where1->andWhere(Pomm\Query\Where::createWhereIn('other_id', array(1,2,3,5,7,11)));

    echo $where1; // (pika = ? OR age < ?) AND other_id IN (?,?,?,?,?,?)

Fields methods
--------------

A very useful property of SQL sets is that they are extendibles. You can add a new field or remove an existing one in a SELECT very easily. All the generic finders described above use the following methods to know what fields to retrieve from queries:

* ``getFields``
* ``getSelectFields($alias)``
* ``getGroupByFields($alias)``

**getFields($table_alias)** is the parent of all the fields getters. It returns an array of the form ``field_alias => $table_alias.$field_name``. Table alias is optional and can be omitted. All other fields getters use ``getFields`` internally and you would use it when to do your own one.

**getSelectFields($alias)** is used by all the finders by also by the update, delete and insert methods in their ``RETURNING`` clause. Overloading this one will change their behavior also.

**getGroupByFields($alias)** is to be used in ``GROUP BY`` clauses. Note that Postgresql > 9.1 does not enforce grouping all the fields present in the select as soon as you group by primary key. So this method is to be used only when using Postgres 9.0 or lower versions.

The following example show how to modify the fields for a table containing user informations::

    public function getSelectFields($alias = null)
    {
        $fields = parent::getSelectFields($alias);
        $alias = is_null($alias) ? $alias."." : '';

        // We do never retrieve password informations
        unset($fields['password']);

        // Add gravatar id in the select
        $fields['gravatar'] = sprintf("md5(%s.email_address)", $alias);

        return $fields;
    }

    // elsewhere in the code
    $employee = $employee_map->findByPk(array('email' => 'pika.chu@gmail.com'));
    $employee->has('password'); // false
    $employee->get('gravatar'); // 6c3e76d8b31679442f089cd3e7edb48a

Note the example above show the use of a Postgresql's function to calculate the gravatar field. It is obviously possible to use all Postgresql operators and functions in the fields, which makes this feature a very powerful ally.

Building custom queries
-----------------------

Even if generic finders may fulfill 90% of developers needs, it is possible to define your own finders using SQL. The generic structures of the SQL with Pomm follow the principle described below::

    SELECT
      %s
    FROM
      %s
    WHERE
      %s

 * The first string is provided by one fields getter method (see `Fields methods`_ above).
 * The second string is the set's source, most of the time a table name. This is provided by the ``getTableName($alias)`` method.
 * The last string is the where clause. If a ``Where`` instance is provided it is as easy as casting it to String.

Fields formatters
-----------------

The problem with the fields getters is that they return an array. This array has to be processed to get a string of fields usable in a SQL query. This is the role of the fields formatters methods:

 * formatFields('method_name', 'table_alias') returns a string with a comma separated list of fields.
 * formatFieldsWithAlias('method_name', 'table_alias') same as above but with fields aliases.

These methods call the fields getter given as *method_name* and return the formatted list of fields::

    $where = new \Pomm\Query\Where::create("age < ?", array(18))
        ->andWhere('main_teacher_id = ?', array(1));

    $sql = sprintf("SELECT %s FROM %s WHERE %s", 
        $this->formatFieldsWithAlias('getSelectFields', 'my_table'),
        $this->getTableName('my_table'),
        (string) $where
        )

    return $this->query->($sql, $where->getValues());

    // This will perform
    SELECT
      "my_table.field1" AS "field1",
      ...
    FROM
      a_table my_table
    WHERE
      age < ? AND main_teacher_id = ?

Complex queries
---------------

The example above is roughly what is coded in ``findWhere``.In real life, it is very likely one needs to join several database tables and their fields. Pomm makes it easy to get other map files from within the one you are coding your finder to use their methods.

::

  // MyDatabase\Blog\PostMap Class
  public function getBlogPostsWithCommentCount(Pomm\Query\Where $where)
  {
    $comment_map = $this->connection->getMapFor('\MyDatabase\Blog\Comment');

    $sql = <<<_
    SELECT
      %s,
      COUNT(c.id) as "comment_count"
    FROM
      %s p
        LEFT JOIN %s c ON
            p.id = c.p_id
    WHERE
        %s
    GROUP BY
        %s
    _;

    $sql = sprintf($sql,
        $this->formatFieldsWithAlias('getSelectFields', 'p'),
        $this->getTableName(),
        $comment_map->getTableName(),
        (string) $where,
        $this->formatFields('getGroupByFields', 'p')
        );

    return $this->query($sql, $where->getValues());
  }

The ``query()`` method is available for your custom queries. It takes 2 parameters, the SQL statement and an optional array of values to be escaped. Keep in mind, the number of values must match the '?' Occurrences in the query.

Whatever you are retrieving, Pomm will hydrate objects according to what is in structure definition of your map class. **Entities do not know about their structure** they just contain data and methods. The entity instances returned here will have this extra field "comment_count" exactly as it would be a normal field. Of course if you update this entity in the database, this field will be ignored. 

Virtual fields
--------------

Adding new fields in the SELECT trough the fields getter methods do not make them mapped to any known type hence not converted with the converter system. It is possible to assign these now "virtual fields" a converter. 

::

    // Map a field added in getSelectFields to then Interval converter.
    $this->addVirtualField('created_since', 'Interval');


This feature is interesting since SQL queries can fetch objects directly::

    SELECT author, array_agg(post) AS posts FROM author JOIN post ON post.author_id = author.id GROUP BY author...;

    +----+-------------------+-------------------------------------
    | id |       name        |                  posts
    +----+-------------------+-------------------------------------
    |  1 | john doe          | "{('post 1', 1, 'some content'),(
    +----+-------------------+-------------------------------------
    |  2 | Edgar             | "{('other post', 2, 'Other content'),
    +----+-------------------+-------------------------------------

Using an entity converter will make an entity instance fetched directly from the database. The example below creates a relationship between the author and the post tables getting all the posts from one author in an array of Post instances::

    // YourDb\SchemaName\AuthorMap

    public function getOneWithPosts($author_name)
    {
        $remote_map = $this->connection->getMapFor('YourDb\SchemaName\Post');

        $sql = <<<_
        SELECT 
          %s,
          array_agg(post) AS posts
        FROM 
          %s 
            LEFT JOIN %s ON 
                author.id = post.author_id 
        WHERE
            author.name = ?
        GROUP BY 
          %s
        _;

        $sql = sprintf(
            $sql,
            $this->formatFieldsWithAlias('getSelectFields', 'author'),
            $this->getTableName('author'),
            $remote_map->getTableName('post'),
            $this->getGroupByFields('author')
        );

        $this->addVirtualField('posts', 'schema_name.post[]');

        return $this->query($sql, array($author_name));
    }

In this example we assume the ``schema_name.post`` type has already been associated with the ``PgEntity`` converter with its map class (see `Entity converter`_). The fetched ``Author`` instances will have an extra attribute ``posts`` containing an array of ``Post`` instances (see `Arrays`_). This is a very powerful feature because you can fetch directly any entity's related objects from the database and hydrate them on the fly.

Collections
===========

Fetching results
----------------

The ``query()`` method return a ``Collection`` instance that holds the PDOStatement with the results. The ``Collection`` class implements the ``Countable`` and ``Iterator`` interfaces so you can foreach on a Collection to retrieve the results:

::

  printf("Your search returned '%d' results.", $collection->count());

  foreach($collection as $blog_post)
  {
    printf("Blog post '%s' posted on '%s' by '%s'.", 
        $blog_post['title'], 
        $blog_post['created_at']->format('Y-m-d'), 
        $blog_post['author']
        );
  }

Sometimes, you want to access a particular result in a collection knowing the result's index. It is possible using the ``has()`` and ``get()`` methods:

::

  # Get an object from the collection at a given index 
  # or create a new one if index does not exist 
  $object = $collection->has($index) ?
    $collection->get($index) : 
    new Object();

Collections have other handful methods like:
 * ``isFirst()``
 * ``isLast()``
 * ``isEmpty()``
 * ``isOdd()``
 * ``isEven()``
 * ``getOddEven()``
 * ``extract()``

Collection types
----------------

Pomm proposes two types of collections: `Collection` and `SimpleCollection`. Because internally, collections are an iterator on a SQL cursor, it is not really possible to rewind that cursor (as long as the PDO_SCROLLABLE_CURSOR is a ugly hack) so the ``SimpleCollection`` is a non scrollable, fire and forget iterator. This means it is fast, light but it is not possible to fetch results twice from it. This is where ``Collection`` class enters the scene. This collection type keeps the fetched results in memory. This can lead to a huge memory consumption but also make possible to develop some useful features:

  * rewindable iterator
  * collection filters

By default, map classes use ``Collection`` instances to ensure backward compatibility with Pomm 1.0 projects. This can be changed by overloading the ``createCollectionFromStatement()`` method of your map class::

    public function createCollectionFromStatement(\PDOStatement $stmt)
    {
        return new \Pomm\Object\SimpleCollection($stmt, $this);
    }

This method allows developers to create their own collection classes. There are no interface to implement but it is advised to inherit from ``SimpleCollection``.

Collection filters
------------------

Pomm's ``Collection`` class can register filters. Filters are just functions that are executed after values were fetched from the database and before the object is hydrated with them (pre hydration filters). These filters take the array of fetched values as parameter. They return an array with values which are then given to the next filter and so on. After all filters are being executed, the values are hydrated in entity instance related the map the collection comes from. 

::

    $collection = $this->query($sql, $values);

    $collection->registerFilter(function($values) {
        $values['good_pika'] = $values['pika'] == 'chu' ? 'Good' : 'Try again';

        return $values;
        });

The code above register a filter that create an extra field in our result set. Every time a result is fetched, this anonymous function will be triggered and the resulting values will be hydrated in the entity.

Collection filters can also be used to create pseudo-relations between entity classes. Imagine we have to retrieve a post with its author. It would be nice if we could fetch selected fields of the author instead of a raw database object.

::

    public function getPostWithAuthor($slug)
    {
        $author_map = $this->connection->getMapFor('\MyDb\MySchema\Author');

        $sql = "SELECT %s, %s FROM %s p JOIN %s a ON p.author_id = a.id WHERE p.slug = ?";

        $sql = sprintf(
            $sql,
            $this->formatFieldsWithAlias('getSelectFields', 'p'),
            $author_map->formatFieldsWithAlias('getRemoteSelectFields', 'a'),
            $this->getTableName(),
            $author_map->getTableName()
        );

        return $this->query($sql, array($slug))
            ->registerFilter(array($author_map, 'createFromForeign'))
            ->current();
    }

The example above shows the use of a special field getter method: *getRemoteSelectFields*. The issued query is::

    SELECT
      "p.field1" AS "field1",
      "p.field2" AS "field2",
      ...
      "p.fieldN" AS "fieldN",
      "a.field1" AS "{author}.field1",
      "a.field2" AS "{author}.field2",
      ....
    FROM
      post p
        JOIN author a ON p.author_id = a.id
    WHERE
      p.slug = ?

After the query is sent, a filter is registered to the collection, the ``AuthorMap::createFromForeign`` method. This callable takes all the ``{author}`` fields of each row, delete them from the result set and hydrate an ``Author`` entity with their values under a 'author' key.   ::

    $post = $post_map->getPostWithAuthor($slug);
    $post->getAuthor(); // returns a Author instance.

Pagers
======

Pager query methods
-------------------

``BaseObjectMap`` instances provide 2 methods that will grant you with a ``Pager`` class. ``paginateQuery()`` and the handy ``paginateFindWhere()``. It adds the correct subset limitation at the end of you query. Of course, it assumes you do not specify any LIMIT nor OFFSET sql clauses in your query. 

The ``paginateFindWhere()`` method acts pretty much like the ``findWhere()`` method (see `Built-in finders`_) which it uses internally. This means the condition can be either a string or a ``Pomm\Query\Where`` instance (see `AND OR: The Where class`_)::

  $pager = $student_map
    ->paginateFindWhere('age < ? OR gender = ?', array(19, 'F'), 'ORDER BY score ASC', 25, 4);

The example below ask Pomm to retrieve the fourth page of students that match some condition with 25 results per page.

The ``paginateQuery()`` acts like the ``query()`` method but you need to provide 2 SQL queries: the one that returns results and the one that counts the total number of rows that first query would return without paging.

Displaying a pager
------------------

``Pager`` instances come with methods to display basic page informations like page count, current page, first result row etc. Here is an example of how to display a page in a twig template::

  <ul>
    {% for student in pager.getCollection() %}
      <li>{{ student }}</li>
    {% endfor %}
  </ul>
  {% if pager.getLastPage() > 1 %}
  <div class="pager"><p>
  <a href="{{ app.url_generator.generate('news') }}">First</a>
  {% if pager.isPreviousPage() %}
  <a href="{{ app.url_generator.generate('news', {'page': pager.getPage - 1}) }}">Previous</a>
  {% else %}
  Previous
  {% endif %}
  News {{ pager.getResultMin() }} to {{ pager.getResultMax() }}
  {% if pager.isNextPage() %}
  <a href="{{ app.url_generator.generate('news', {'page': pager.getPage + 1} ) }}">Next</a>
  {% else %}
  Next
  {% endif %}
  <a href="{{ app.url_generator.generate('news', {'page': pager.getLastPage} ) }}">Last</a>
  </p></div>
  {% endif %}

***********
Connections
***********

Overview
========

Map classes provider
--------------------

As soon as you have a database instance, you can create new connections. This is done by using the ``getConnection()`` method. Connections are the way to
 * Retrieve map classes instances
 * Manage transactions

The entities are stored in a particular database. This is why only connections to this base are able to give you associated map classes::

  $map = $database()
    ->getConnection()
    ->getMapFor('College\School\Student'); 
  

It is possible to force the creation of a new Connection using the `createConnection()` method call but the `getConnection()` is preferred since it creates automatically a new connection if none exist, it returns the current one otherwise.

Identity mappers
----------------

Connections are also the way to tell the map classes to use or not an ``IdentityMapper``. An identity mapper is an index kept by the connection and shared amongst the map instances. This index ensures that when an object is retrieved twice from the database, the same ``Object`` instance will be returned. This is a very powerful (and dangerous) feature. There are two ways to declare an identity mapper to your connections:
 * in the ``Database`` parameters. All the connections created for this database will use the given ``IdentityMapper`` class.
 * when instanciating the connection through the ``createConnection()`` call. This enforces the parameter given to the ``Database`` class if any. 

 ::

  $map = $database()
    ->createConnection(new \Pomm\Identity\IdentityMapperSmart())
    ->getMapFor('College\School\Student');

  $student1 = $map->findByPK(array('id' => 3));
  $student2 = $map->findByPK(array('id' => 3));

  $student1->setName('plop');
  echo $student2->getName();    // plop

It is often a good idea to have an identity mapper by default, but in some cases you will want to switch it off and ensure all objects you fetch from the database do not come from the mapper. This is possible passing the ``Connection`` an instance of ``IdentityMapperNone``. It will never keep any instances. There are two other types of identity mappers:
 * ``IdentityMapperStrict`` which always return an instance if it is in the index.
 * ``IdentityMapperSmart`` which checks if the instance has not been deleted. If data are fetched from the db, it checks if the instance kept in the index has not been modified. If not, it merges the fetched values with its instance.

It is of course always possible to remove an instance from the mapper by calling the ``removeInstance()``. You can create your own identity mapper, just make sure your class implement the ``IdentityMapperInterface``. Be aware the mapper is called for each values fetched from the database so it has a real impact on performances.

**Important** The identity mappers strict and smart rely on the use of primary keys to identify records. If you use a table without primary keys, these identity mappers will **NOT** store any of these entities.

Transactions
============

Standard transactions
---------------------

By default, connections are in auto-commit mode which means every change in the database is committed on the fly. Connections offer the way to enter in a transaction mode::

  $cnx = $service->getDatabase()
    ->createConnection();
  $cnx->begin();
  try {
    # do things here
    $cnx->commit();
  } catch (Pomm\Exception\Exception $e) {
    $cnx->rollback();
  }

The transaction type is determined by ``ISOLATION LEVEL`` you set in your connection's parameters (see `Database class and isolation level`_) 

Isolation level must be one of ``Pomm\Connection\Connection::ISOLATION_READ_COMMITTED``, ``ISOLATION_READ_REPEATABLE`` or ``ISOLATION_SERIALIZABLE``. Check your Postgresql version for the available levels. Starting from pg 9.1, what was called ``SERIALIZABLE`` is called ``READ_REPEATABLE`` and ``SERIALIZABLE`` is a race for the first transaction to COMMIT. This means if the transaction fails, you may just try again until it works. Check the `postgresql documentation <http://www.postgresql.org/docs/9.1/static/transaction-iso.html>`_ about transactions for details.

Partial transactions and savepoints
-----------------------------------

Sometime, you may need to split transactions into parts and be able to perform partial rollback. Postgresql lets you use save points in your transaction::

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

Query filter chain
==================

LoggerFilterChain
-----------------
The Connection class also holds and the heart of Pomm's query system: the ``QueryFilterChain``. The filter chain is an ordered stack of filters which can be executed. As the first filter is executed it can call the following filter. The code before the next filter call will be executed before and the code placed after will be run after. 
This mechanism aims at wrapping the query system with tools like loggers or event systems. It is also possible to bypass completely the query execution as long as you return a ``PDOStatement`` instance.

::

  $database = new Pomm\Connection\Database(array('dsn' => 'pgsql://user/database'));
  $logger = new Pomm\Tools\Logger();

  $connection = $database->createConnection();
  $connection->registerFilter(new Pomm\FilterChain\LoggerFilter($logger));

  $students = $connection
    ->getMapFor('MyDb\School\Student')
    ->findWhere('age > ?', array(18), 'ORDER BY level DESC');

  $logger->getLogs() 
  /* Array( 
       "1327047962.9422" => Array(
         'sql'       => 'SELECT ... FROM school.student WHERE age > ? ORDER BY level DESC', 
         'params'    => array(18), 
         'duration'  => 0.003079,
         'results'   => 23
       ))
   */

Writing a Filter
----------------
Writing a filter is very easy, it just must implement the ``FilterInterface``.

::

  class MyFilter implements \Pomm\Filter\FilterInterface
  {
      public function execute(\Pomm\Filter\QueryFilterChain $query_filter_chain)
      {
          // Do something before the query is executed

          // Call the next filter
          // If you do not, the query will never be executed. 
          // Be sure to return a PDOStatement or throw an Exception.
          $stmt = $query_filter_chain->executeNext($query_filter_chain);

          // Do something after the query is executed
  
          return $stmt;
      }
  }

You can register as many filters as you want but keep in mind filters are executed for every single query so it may slow down dramatically your application. 

*****
Tools
*****

Map generation tools
====================

Pomm comes with handy tools to generate map classes that reflect what is in your database. 

Database Inspector
------------------

The database inspector class proposes methods to scavenge structure informations in the database. It is used by the Map generators and you can use it in your own scripts. 

CreateBaseMapTool
-----------------

This class is the main generator class. 

 * It inspects the database for the given table / view.
 * It creates the directory structure for your namespaces.
 * It generates the BaseMap file from the structure detected in the database.
 * It generates empty entity and map files if they do not exist.

This class accepts the following parameters:

  * "database" a \Pomm\Connection\Database instance (mandatory).
  * "table" or "oid" (mandatory)
  * "prefix_dir" Where to generate the tree on the disk (mandatory).
  * "schema" (default to 'public').
  * "parent_namespace" When inheritance is found, override the default namespace for parent.
  * "namespace" (default to '%dbname%\%schema%') The namespace placeholder.
  * "extends" (default to \Pomm\Object\BaseObjectMap).
  * "class_name" The corresponding entity class. (default camel cased table's name).

**table** or **oid**

If you give both, the oid has precedence over the name. 

**prefix_dir**

This is the root directory from which the directory tree will be built. The directory by default respects the PSR-0 standard to allow autoloading according to namespaces but you can change it.

**schema** 
The database schema name where the table or view is located.

**namespace**
The namespace parameter is a placeholder. There are 2 values that can be substituted with their camel cased name: *%schema%* and *%dbname%*. By default, the namespace follows the directory structure.

**parent_namespace**
When database table inheritance is found, this parameter override the default namespace for the parent map class. Otherwise the parent is assumed to be in the default namespace.

**extends**
By default, the generated base class extends ``\Pomm\Object\BaseObjectMap`` but you might want to set another class. The final parent of the map class must be BaseObjectMap in the end.

**class_name**
In case of generating map class for a view, it may be a good idea to tell Pomm that entities fetched by this map are something else than it thinks. This makes possible to have different views of the same table fetching the same entities from them.

ScanSchemaTool
--------------

The schema scanning tool takes a schema name as parameter and then launches CreateBaseMapTool for each table / view it finds in it. The expected parameters are the following:

  * "database" a \Pomm\Connection\Database instance (mandatory).
  * "prefix_dir" Where to generate the tree on the disk (mandatory).
  * "schema" (default to 'public').
  * "namespace" (default to '%dbname%\%schema%') The namespace placeholder.
  * "extends" (default to \Pomm\Object\BaseObjectMap).
  * "parent_namespace" When inheritance is found, override the default namespace for parent.
  * "exclude" (optional) an array of tables/views not to generate files from.

Most of these parameters are sent to the ``CreateBaseMapTool`` as is. The only different parameter is

**exclude**
An array of tables/views to ignore. 

Here is a sample of code to generate map classes from all the tables/views in a database schema::

  <?php

  require __DIR__.'/vendor/pomm/test/autoload.php';

  $database = new Pomm\Connection\Database(array(
          'dsn'  => 'pgsql://nss_user:nss_password@localhost/nss_db',
          'name' => 'my_db'
          ));

  $scan = new Pomm\Tools\ScanSchemaTool(array(
      'prefix_dir'=> __DIR__,
      'schema' => 'transfo',
      'database' => $database
  ));

  $scan->execute();

This will parse the postgresql's schema named *transfo* to scan it for tables and views. Then it will generate automatically the *BaseMap* files with the class structure and if map files or entity files do not exist, will create them. By default, with the code above, the following tree structure will be created from the directory this code is invoked::

    /prefix/dir/MyDb
    └── Transfo
        ├── Base
        │   └── TransformerMap.php
        ├── TransformerMap.php
        └── Transformer.php

