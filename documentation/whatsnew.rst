======================
What's new in Pomm 1.1
======================

Postgres 9.2 types support
==========================

Pomm now offers native converters for **ranges** (tsrange, int4range, int8range numrange) and **Json** types. It has better support for **Interval** type and you can directly set PHP **DateTime** instances as parameters of your prepared queries.

More handy methods for your controllers
=======================================

It benefits from Postgresql's *RETURNING* clause so you can directly write in the database and get the according instance:

 * `$entity = $map->createAndSaveObject(array($form->getValues()))` 
 * `$entity = $map->updateByPk(array('id' => $form['id']), array('field_to_update' => $form['field_to_update'], ...))`
 * `$array = $map->findAll()->extract();` 
 * `$collection = $map->findAll('ORDER BY created_at LIMIT 5');`

Better entities and collections
===============================

The behavior of entities in 1.0 was unclear regarding field detection and NULL values. It was not really possible to delete a field from an entity instance. It is now possible with the `$entity->clear('my_field')` or `$entity->clearMyField()` or `unset($entity['my_field'])` method calls.

The `extract()` method has been changed so it can also flatten any possible embed entities. This is desirable when by exemple dealing with web services to export everything in JSON format. The behavior of the old `extract()` method has been moved in the better named `getFields()` method.

The Collection had some improvements too. In 1.0, the use of scrollable cursors has been abandoned because this was too obtrusive from a SQL perspective. To be able to scroll on result sets more than once, the Collection system had to cache all the fetched results that could lead to a huge memory consumption. There is a new `SimpleCollection` class that just implements a non rewindable iterator. It does not support hydration filters nor cachine, it just let you foreach once over results as fast as PHP can which is what you want 99.9% of the time. The `Collection` class is still here providing the same features as 1.0 and it is still the default collection used by Pomm 1.1. You can switch to `SimpleCollection` or whatever other `Iterator` implementation you want just by overloading your map classes `createCollectionFromStatement()` method.

To end with `Collection` class, all the filter methods now implement the fluid interface so you can register filters and return results without using temporary variable::

    return $this->query($sql, $values)
        ->resetFilters()
        ->registerFilter($callable1)
        ->registerFilter($callable2)
        ;

Fields getters are awesome
==========================

The `getFields()` methods family is one of the coolest features of Pomm allowing developers to add their own fields to all or just in one query. In an other hand, it was tedious to filter fields not to fetch some of them (passwords, technical fields etc.). Fields are now handled as an associative array in the map class with the key becoming the SQL field's alias name so it is now easy to filter fields with PHP's `array_filter` or `array_map` functions. 

Aside of that, it is tedious to always have to `join(', ', $map->getSelectFields(...` to nicely format SQL queries. Pomm 1.1 comes with two formatters `formatFields($method_name, $table_alias)` and `formatFieldsWithAlias($method_name, $table_alias)`. These formatters take a getField method name as first argument, they use it to get the fields and format the output as a string to fit your SQL queries.

    ::

    echo $this->formatFieldsWithAlias('getFields', 'pika');

    "pika.field1" AS "field1", "pika.field2" AS "field2", ... 

There is also the field getters triggered by *Collection filters*. This was noted as deprecated in the 1.0 version. It is finally kept in the 1.1 version because it may be desirable to split a non nested result set in several entities.

Generation tools are not forgotten
==================================

The 1.0 tools lacked output when used from the command line. There is now a `OutputStack` that manage messages. Generated schemas and namespaces can be formatted the way you want using a placeholder syntax. It is also now possible to exclude tables from a schema generation task.

The last but not the least
==========================

The first task toward the 1.1 version has been to rewrite the whole test suite using PHPUnit. It is now easier to develop features and add the according tests as PHPUnit is very standard in the PHP world. This also makes Pomm testable with Travis the open source continuous integration service. The first side effect of this rewrite has been to spot numerous little bugs and put light on some inconsistent behaviors. Few of them still remain today (ie binary string to Pg's bytea type conversion) but the vast majority have been fixed. Most of those corrections were also backported to the 1.0 branch.

=========================
Migrating from 1.0 to 1.1
=========================

Migrating from 1.0 to 1.1 should not be a hassle, here is a list of points that break compatibility between 1.0 and 1.1:

PgEntity refactoring
====================

PgEntity converter's constructor now takes a `BaseObjectMap` instance as argument instead of `Database` and the class name::

    // 1.0
    $database->registerConverter(
        'WeatherProbe', 
        new Pomm\Converter\PgEntity($database, '\Db\Weather\WeatherProbe'),
        array('weather_probe')
        );

    // 1.1
    $database->registerConverter(
        'WeatherProbe', 
        new Pomm\Converter\PgEntity($weather_probe_map),
        array('weather_probe')
        );

This is very useful if you want `PgEntity` to deal with a map class that is custom made with extra virtual fields or filters.

`See the related ticket <https://github.com/chanmix51/Pomm/issues/30>_`.

executeAnonymousQuery
=====================

This method is now a Connection instance method instead of a Database instance method.

`See the related ticket <https://github.com/chanmix51/Pomm/issues/29>_`.

Entity getter
=============

The `get($field)` entity's method throws an exception when the field does not exist in 1.1. In 1.0, the behavior was to return NULL which lead to bugs with typos in method names being silently ignored.

`See the related ticket <https://github.com/chanmix51/Pomm/issues/48>_`.
