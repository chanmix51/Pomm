------------------
Pomm Documentation
------------------

.. contents::

Overview
--------

Pomm is a fast, lightweight, efficient model manager for Postgresql written in PHP. It can be seen as an enhanced object hydrator above PDO with the following features:

 * Database Inspector to build automatically your PHP model files. Table inheritance from Pg will make your model class to inherit from each other.
 * Namespaces are used to ovoid collision between objects located in different Pg schemas.
 * Types are converted on the fly. 't' and 'f' Pg booleans are converted into PHP booleans, so are binary, arrays, geometric and your own data types.
 * Queries results are fetched on demand to keep minimal memory fingerprint.
 * It is possible to register anonymous PHP functions to filter fetched results prior to hydration.
 * Model objects are extensible, simply add fields in your SELECT statements.
 * Pomm uses an identity mapper, fetching twice the same rows will return same instances.
 * You can register code to be executed before and/or after each query (logs, filters, event systems ...)

Let's have an overview of Pomm's components:

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
Collections
  A collection holds a query result. You can iterate over it with a _foreach_ to fetch Entity instances but it proposes a lot more of handy methods. 
Converter
  By default, each database has the basic converters registered in order to convert native types from/to Postgresql/PHP. Some of them are optional and you can write your own for your custom database types.

Dealing with databases
----------------------

Service: the database provider
==============================

The *Service* class just stores your *Database* instances and provides convenient methods to create connections from them. There are several ways to declare databases to the service class. Either you use the constructor passing an array "name" => "connection parameters" or you can use the *setDatabase* method of the service class.::

    # The two examples below are equivalent
    # Using the constructor
    $service = new Pomm\Service(array(
      'db_one' => array(
        'dsn' => 'pgsql://user:pass@host:port/db_a'
      ),
      'db_two' => array(
        'dsn' => 'pgsql://otheruser:hispass@!/path/to/socket/directory!/db_b',
        'class' => 'App\MyDb',
        'identity_mapper' => 'App\MyIdentityMapper'
      )
      ));
    
    # Using the setDatabase method
    $service = new Pomm\Service();
    $service->setDatabase('db_one', new Pomm\Connection\Database(array(
      'dsn' => 'pgsql://user:pass@host:port/db_a'
    )));
    $service->setDatabase('db_two', new App\MyDb(array(
      'dsn' => 'pgsql://otheruser:hispass@!/path/to/socket/directory!/db_b',
      'identity_mapper' => 'App\MyIdentityMapper'
    )));

The *setDatabase* method is used internally by the constructor. The parameters may be any of the following:
 * "dsn": a URL like string to connect the database. It is in the form pgsql://user:password@host:port/database_name (**mandatory**)
 * "class": The *Database* class to instantiate as a database. This class must extend Pomm\\Database as we will see below.
 * "isolation": transaction isolation level. Must be one of Pomm\\Connection\\Connection::ISOLATION_READ_COMMITTED, ISOLATION_READ_REPEATABLE or ISOLATION_SERIALIZABLE (default ISOLATION_READ_COMMITTED). Check your Postgresql version for the available levels. Starting from pg 9.1, what was called SERIALIZABLE is called READ_REPEATABLE and SERIALIZABLE is a race for the first transaction to COMMIT. Check the `documentation_` for details.

`_documentation` http://www.postgresql.org/docs/9.1/static/transaction-iso.html

Once registered, you can retrieve the databases with their name by calling the *getDatabase* method passing the name as argument. If no name is given, the first declared *Database* will be returned.

The **dsn** parameter format is important because it interacts with the server's access policy.

 * **socket connection**
 * *pgsql://user/database* Connect *user* to the db *database* without password through the Unix socket system. This is the DSN's shortest form.
 * *pgsql://user:pass/database* The same but with password.
 * *pgsql://user:pass@!/path/to/socket!/database* When the socket is not in the default directory, it is possible to specify it in the host part of the DSN. Note it is surrounded by '!' and there are NO ending /. Using the «!» as delimiter assumes there are no «!» in your socket's path. But you don't have «!» in your socket's path do you ?
 * *pgsql://user@!/path/to/socket!:port/database* Postgresql's listening socket name are the same as TCP ports. If different than default socket, specify it in the port part.
 * **TCP connection**
 * *pgsql://user@host/database* Connect *user* to the db *database* on host *host* using TCP/IP.
 * *pgsql://user:pass@host:port/database* The same but with password and TCP port specified. 

The **identity_mapper** option gives you the opportunity to register an identity mapper. When connections are created, they will instantiate the given class. By default, the Smart IM is loaded. This can be overridden for specific connections (see the identity mapper section below).

Database and converters
=======================

The *Database* class brings access to mechanisms to create connections and transactions and also register converters. A *Converter* is a class that translates a data type from Postgresql to PHP and from PHP to Postgresql. By default, the following converters are registered, this means you can use them without configuring anything:
 * Boolean: convert postgresql 't' and 'f' to PHP boolean value
 * Number: convert postgresql 'smallint', 'bigint', 'integer', 'decimal', 'numeric', 'real', 'double precision', 'serial', 'bigserial' types to numbers
 * String: convert postgresql 'varchar', 'uuid', 'xml' and 'text' into PHP string
 * Timestamp: convert postgresql 'timestamp', 'date', 'time' to PHP DateTime instance.
 * Interval: convert postgresql's 'interval' type into PHP SplInterval instance. 
 * Binary: convert postgresql's 'bytea' type into PHP string.

Other types are natively available in postgresql databases but are not loaded automatically by Pomm:

 * Point: postgresql 'point' representation as Pomm\\Type\\Point instance.
 * Segment : 'segment' representation as Pomm\\Type\\Segment.
 * Circle : 'circle' representation as Pomm\\Type\\Circle.

Postgresql contribs come with handy extra data type (like HStore, a key => value array and LTree a materialized path data type). If you use these types in your database you have to register the according converters from your database instance::

  # The HStore converter converts a postgresql HStore to a PHP associative array and the other way around.
  # The following line registers the HStore converter to the default database.
  
  $service->getDatabase()
    ->registerConverter('HStore', new Pomm\Converter\PgHStore(), array('hstore'));

Arguments to instanciate a *Converter* are the following:
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
      $this->registerConverter('Point', new Converter\Pgpoint(), array('point'));
      $this->registerConverter('Circle', new Converter\PgCircle(), array('circle'));
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
 * they own the structure of the entities 
 * They act as Entity provider 

Every action you will perform with your entities will use a Map class. They are roughly the equivalent of Propel's *Peer* classes. Although it might look like Propel, it is important to understand unlike the normal Active Record design pattern, entities do not even know their structure and how to save themselves. You have to use their relative Map class to save them.
Map classes represent a structure in the database and provide methods to retrieve and save data with this structure. To be short, one table or view <=> one map class.

To be able to be the bridge between your database and your entities, all Map classes **must** at the end extends *Pomm\\Object\\BaseObjectMap* class. This class implements methods that directly interact with the database using the PDO layer. These methods will be explained in the chapter how to query the database.

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

If the table is stored in a special database schema, it must appear in the *object_name* attribute. If you do not use schemas, postgresql will store everything in the *public* schema. You do not have to specify it in the *object_name* attribute but it will be used in the class namespace. *public* is also a reserved keyword of PHP, the namespace for the *public* schema is *PublicSchema*.

Let's say we have the following table *student* in the database *College*:

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
  namespace College\PublicSchema\Base;

  use Pomm\Object\BaseObjectMap;
  use Pomm\Exception\Exception;

  abstract class StudentMap extends BaseObjectMap
  {
      public function initialize()
      {
          $this->object_class =  'College\PublicSchema\Student';
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
  namespace College\School\Base;
  ...
          $this->object_class =  'College\School\Student';
          $this->object_name  =  'school.student';
  

Querying the database
---------------------

Create finders
==============

The first time you generate the *BaseMap* classes, it will also generate the map classes and the entity classes. Using the example with student, the empty map file should look like this::

  <?php
  namespace College\School;

  use College\School\Base\StudentMap as BaseStudentMap;
  use Pomm\Exception\Exception;
  use Pomm\Query\Where;
  use College\School\Student;

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
  

Of course, this is not very useful, because the date is very likely to be a parameter. A finder *getYoungerThan* would be::

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

All queries are prepared, this might increase the performance but it certainly increases the security. The argument here will automatically be escaped by the database and ovoid SQL-injection attacks. If a suffix is passed, it is appended to the query **as is**. The suffix is intended to allow developers to specify sorting a subset parameters to the query. As the query is prepared, a multiple query injection type attack is not directly possible but be careful if you pass values sent by the customer.

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

Although it is possible to write whole plain queries by hand in the finders, this may induce coupling between your classes and the database structure. To ovoid coupling, the Map class owns the following methods: *getSelectFields*, *getGroupByFields* and *getFields*. It is important that table names are also retrieved with the *getTableName* method to keep the query correct if the table name changes over time.

::

  // MyDatabase\Blog\PostMap Class
  public function getBlogPostsWithCommentCount(Pomm\Query\Where $where)
  {
    $sql = sprintf('SELECT %s, COUNT(c.id) as "comment_count" FROM %s JOIN %s ON p.id = c.post_id WHERE %s GROUP BY %s',
        join(', ', $this->getSelectFields('p')),
        $this->getTableName('p'),
        $this->Connection->getMapFor('MyDatabase\Blog\Comment')->getTableName('c'),
        $where,
        join(', ', $this->getGroupByFields('p'))
        );

    return $this->query($sql, $where->getValues());
  }

The *query* method is available for your custom queries. It takes 2 parameters, the SQL statement and an optional array of values to be escaped. Keep in mind, the number of values must match the '?' Occurrences in the query.

Whatever you are retrieving, Pomm will hydrate objects according to what is in *$this->object_class* of your map class. The entity instances returned here will have this extra field "comment_count" exactly as it would be a normal field. You can use a *Where* instance everywhere as their *toString* method returns the condition as a string and the *getValues* method return the array with the values to be escaped.

Extending the model
===================

All the finders internally use the *getSelectFields()* method to ovoid using the star notation (*) of course but also to make the fields list extendible. Imagine your model as a *created_at* field that stores the timestamp of the creation for every rows in a table. You can overload the *getSelectFields()* method to add a *created_since* field that returns the time interval between now and *created_at*. It will then be used in **all** finders and this extra attribute will never be saved as it does not belong to the Map structure.

It is also possible to reduce a model, stripping by example a *password* field or any other field that does not make sense outside the database. The Map classes do propose 3 kind of field selectors:
 * getSelectFields($alias) 
 * getGroupByFields($alias)
 * getFields() // used by both getSelectFields and getGroupByFields
 * getRemoteSelectFields($alias) // uses getSelectFields()

*getRemoteSelectFields()* is a bit special as it returns the same fields as *getSelectFields()* but it aliases the results like the following::

  print join(', ', $author_map->getRemoteSelectFields('a'));
  // a.id AS "author{id}, a.first_name AS "author{first_name}, ...

It is intended for use with the collection filters explained below.

Collections
===========

The *query* method return a *Collection* instance that holds the PDOStatement with the results. The *Collection* class implements the *Coutable* and *Iterator* interfaces so you can foreach on a Collection to retrieve the results:

::

  printf("Your search returned '%d' results.", $collection->count());

  foreach($collection as $blog_post)
  {
    printf("Blog post '%s' posted on '%s' by '%s'.", $blog_post['title'], $blog_post['created_at']->format('Y-m-d'), $blog_post['author']);
  }

Sometimes, you want to access a particular result in a collection knowing the result's index. It is possible using the *has* and *get* methods:

::

  # Get the an object from the collection at a given index or create a new one
  if index does not exist 
  $object = $collection->has($index) ?
    $collection->get($index) : 
    new Object();

Collections have other handful methods like:
 * *isFirst()*
 * *isLast()*
 * *isEmpty()*
 * *isOdd()*
 * *isEven()*
 * *getOddEven()*

Pomm's *Collection* class can register filters. Filters are just functions that are executed after values were fetched from the database and before the object is hydrated with values. These filters take the array of fetched values as parameter. They return an array with the values. After all filters are being executed, the values are used to hydrate the Object instance related the the Map instance the Collection comes from. This is very convenient to create pseudo relationship between objects:

::

  # This filter triggers the *createFromForeign* method of the *AuthorMap*
  # class. It takes all the fields named *author{%s}* to hydrate a *Author*
  # object and set it in the values.
  # SELECT
  #   article.id,
  #   article.title,
  #   ...
  #   author.id AS "author{id}",
  #   author.name AS "author{name}",
  #   ...
  # FROM
  #   schema.article article
  #     JOIN schema.author author ON article.author_id = author.id
  # WHERE
  #     article.id = ?
  #
  # ArticleMap.php

  $author_map = $this->connection->getMapFor('Author');
  $sql = sprintf("SELECT %s FROM %s JOIN %s ON article.author_id = author.id WHERE article.id = ?",
    join(', ', array_merge($this->getSelectFields('article'), $author_map->getRemoteSelectFields('author'))),
    $this->getTableName('article'),
    $author_map->getTableName('author')
    );

  $collection = $this->query($sql, $id);
  $collection->registerFilter(function($values) use ($author_map) { return $author_map->createFromForeign($values); });

  foreach($collection as $article)
  {
    printf("%s wrote the article '%s'.", $article->getAuthor()->getName(), $article->getTitle());
  }

Pagers
======

*BaseObjectMap* instances provide 2 methods that will grant you with a *Pager* class. *paginateQuery()* and the handy *paginateFindWhere*. It adds the correct subset limitation at the end of you query. Of course, it assumes you do not specify any LIMIT nor OFFSET sql clauses in your query. Here is an example of how to use retrieve and use a *Pager*:

::

  # In your controller
  # Retrieve femal students or aged under 19 sorted by score
  # 25 results per page, page 4

  $pager = $student_map->paginateFindWhere('age < ? OR gender = ?', array(19, 'F'), 'ORDER BY score ASC', 25, 4);

  # In your twig template
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

Note that the *get* can take an array with multiple attributes::

  $entity->set('pika', 'chu');
  $entity->set('plop', true);

  $entity->get(array('pika', 'plop')); // returns array('pika' => 'chu', 'plop' => true);
  $entity->get($map->getPrimaryKey()); // returns the primary key if set.

*BaseObject* uses magic getters and setters to dynamically build the according methods.

::

  $entity = new MyEntity();
  $entity->hasPika();   // false
  $entity->setPika('chu');
  $entity->hasPika();   // true
  $entity->getPika()    // chu

This allow developers to overload accessors. The methods *set* and *get* are only used within the class definition and should not be used outside unless you want to bypass any overload that could exist.

Entities implement PHP's *ArrayAccess* interface to use the accessors if any. This means you can have easy access to your entity's data in your templates without bypassing accessors !

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

Entities are the end of the process, they are the data. Unlike Active Record where entities know how to manage themselves, with Pomm, entities are just data container that may embed processes. 

::

  $entity = new MyEntity();
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

  $map->updateOne($entity, array('pika')); // UPDATE ... set pika='...'

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

Map Instances provider
======================

As soon as you have a database instance, you can create new connections. This is done by using the *createConnection* method. Connections are the way to
 * Retrieve *Map Classes* instances
 * Manage transactions

The entities are stored in a particular database. This is why only connections to this base are able to give you associated Map classes::

  $map = $service->getDatabase()->createConnection()
    ->getMapFor('College\School\Student'); 
  

Identity mappers
================

Connections are also the way to tell the map classes to use or not an *IdentityMapper*. An indentity mapper is an index kept by the connection and shared amongst the map instances. This index ensures that if an object is retrieved twice from the database, the same *Object* instance will be returned. This is a very powerful (and dangerous) feature. There are two ways to declare an identity mapper to your connections:
 * in the *Database* parameters. All the connections created for this database will use the given *IdentityMapper* class.
 * when instanciating the connection through the *createConnection* call. This enforces the parameter given to the *Database* class if any. 

 ::

  $map = $service->getDatabase()
    ->createConnection(new \Pomm\Identity\IdentityMapperSmart())
    ->getMapFor('College\School\Student');

  $student1 = $map->findByPK(array('id' => 3));
  $student2 = $map->findByPK(array('id' => 3));

  $student1->setName('plop');
  echo $student2->getName();    // plop

It is often a good idea to have an identity mapper by default, but in some cases you will want to switch it off and ensure all objects you fetch from the database do not come from the mapper. This is possible passing the *Connection* an instance of *IdentityMapperNone*. It will never keep any instances. There are two other types of identity mappers:
 * *IdentityMapperStrict* which always return an instance if it is in the index.
 * *IdentityMapperSmart* which checks if the instance has not been deleted. If data are fetched from the db, it checks if the instance kept in the index has not been modified. If not, it merges the fetched values with its instance.

It is of course always possible to remove an instance from the mapper by calling the *removeInstance()*. You can create your own identity mapper, just make sure your class implement the *IdentityMapperInterface*. Be aware the mapper is called for each values fetched from the database so it has a real impact on performances.

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

Query filter chain
==================

The Connection class also holds an the heart of Pomm's query system: the *QueryFilterChain*. The filter chain is an ordered stack of filters which can be executed. As the first filter is executed it can call the following filter. The code before the next filter call will be executed before and the code placed after will be run after. 
This mechanism aims at wrapping the query system with tools like loggers or event systems. It is also possible to bypass completely the query execution as long as you return a PDOStatement instance.

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

Writing a filter is very easy, it just must implement the *FilterInterface*.

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
      'database' => $service->getDatabase(),
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

