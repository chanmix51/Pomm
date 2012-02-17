<?php

namespace Pomm\Test;

use Pomm\Service;
use Pomm\Object\BaseObject;
use Pomm\Object\BaseObjectMap;
use Pomm\Object\Collection;
use Pomm\Converter;
use Pomm\Connection\Database;

require "autoload.php";

class TestTableMap extends BaseObjectMap
{
    protected function initialize()
    {
        $this->object_class =  'Pomm\Test\TestTable';
        $this->object_name  =  'pomm_test.book';
        $this->field_definitions  = array(
            'id'               =>    'Number',
            'created_at'       =>    'Timestamp',
            'last_in'          =>    'Timestamp',
            'last_out'         =>    'Timestamp',
            'title'            =>    'String',
            'authors'          =>    'String[]',
            'is_available'     =>    'Boolean',
            'location'         =>    'Point',
        );
        $this->pk_fields    = array('id');
    }

    public function createTable()
    {
        $sql = sprintf("CREATE SCHEMA %s", reset(preg_split('/\./', $this->object_name)));
        $this->connection->getDatabase()->executeAnonymousQuery($sql);
        $sql = sprintf("CREATE TABLE %s (id SERIAL PRIMARY KEY, created_at TIMESTAMP NOT NULL DEFAULT now(), last_out TIMESTAMP, last_in TIMESTAMP, title VARCHAR(256) NOT NULL, authors VARCHAR(255)[] NOT NULL, is_available BOOLEAN NOT NULL DEFAULT true, location POINT)", $this->object_name);
        $this->connection->getDatabase()->executeAnonymousQuery($sql);
    }

    public function dropTable()
    {
        $objects = preg_split('/\./', $this->object_name);
        $sql = sprintf("DROP SCHEMA %s CASCADE;", reset($objects));
        $this->connection->getDatabase()->executeAnonymousQuery($sql);
    }
}

class TestTable extends BaseObject
{
    public function getTitle()
    {
        return strtolower($this->get('title'));
    }

    public function setTitle($title)
    {
        $this->set('title', strtoupper($title));
    }

    public function hasTitleAndAuthors()
    {
        return $this->has('authors') && $this->has('title');
    }
}

class TestConverterMap extends BaseObjectMap
{
    protected function initialize()
    {
        $this->object_class =  'Pomm\Test\TestConverter';
        $this->object_name  =  'pomm_test.converter';
        $this->field_definitions  = array(
            'id'               => 'Number',
            'created_at'       => 'Timestamp',
            'something'        => 'String',
            'is_true'          => 'Boolean',
            'precision'        => 'Number',
            'probed_data'      => 'Number',
            'binary_data'      => 'Binary',
            'ft_search'        => 'String',
            'times'            => 'Timestamp[]',
        );
        $this->pk_fields    = array('id');
    }

    public function createTable()
    {
        try
        {
            $this->connection->begin();
            $objects = preg_split('/\./', $this->object_name);
            $sql = sprintf("CREATE SCHEMA %s", reset($objects));
            $this->connection->getDatabase()->executeAnonymousQuery($sql);

            $sql = sprintf("CREATE TABLE %s (id SERIAL PRIMARY KEY, created_at TIMESTAMP NOT NULL DEFAULT now(), something VARCHAR, is_true BOOLEAN, precision FLOAT, probed_data NUMERIC(4,3), binary_data BYTEA, ft_search tsvector, times TIMESTAMP[])", $this->object_name);
            $this->connection->getDatabase()->executeAnonymousQuery($sql);
            $this->connection->commit();
        }
        catch (Exception $e)
        {
            $this->connection->rollback();
            throw $e;
        }
    }

    public function dropTable()
    {
        $objects = preg_split('/\./', $this->object_name);
        $sql = sprintf("DROP SCHEMA %s CASCADE", reset($objects));
        $this->connection->getDatabase()->executeAnonymousQuery($sql);
    }

    public function addPoint()
    {
        $this->connection->getDatabase()->executeAnonymousQuery(sprintf("ALTER TABLE %s ADD COLUMN test_point point", $this->object_name));
        $this->connection->getDatabase()->registerConverter('Point', new Converter\PgPoint(), array('point'));
        $this->addField('test_point', 'Point');
    }

    public function addLseg()
    {
        $this->connection->getDatabase()->executeAnonymousQuery(sprintf("ALTER TABLE %s ADD COLUMN test_lseg lseg", $this->object_name));
        $this->connection->getDatabase()->registerConverter('Lseg', new Converter\PgLseg(), array('lseg'));
        $this->addField('test_lseg', 'Lseg');
    }

    public function addHStore()
    {
        $this->connection->getDatabase()->executeAnonymousQuery(sprintf("ALTER TABLE %s ADD COLUMN test_hstore hstore", $this->object_name));
        $this->connection->getDatabase()->registerConverter('HStore', new Converter\PgHStore(), array('hstore'));
        $this->addField('test_hstore', 'HStore');
    }

    public function addCircle()
    {
        $this->connection->getDatabase()->executeAnonymousQuery(sprintf("ALTER TABLE %s ADD COLUMN test_circle circle", $this->object_name));
        $this->connection->getDatabase()->registerConverter('Circle', new Converter\PgCircle(), array('circle'));
        $this->addField('test_circle', 'Circle');
    }

    public function addInterval()
    {
        $this->connection->getDatabase()->executeAnonymousQuery(sprintf("ALTER TABLE %s ADD COLUMN test_interval interval", $this->object_name));
        $this->connection->getDatabase()->registerConverter('Interval', new Converter\PgInterval(), array('interval'));
        $this->addField('test_interval', 'Interval');
    }

    public function addXml()
    {
        $this->connection->getDatabase()->executeAnonymousQuery(sprintf("ALTER TABLE %s ADD COLUMN test_xml xml", $this->object_name));
        $this->addField('test_xml', 'String');
    }

    public function addPeriod()
    {
        $this->connection->getDatabase()->executeAnonymousQuery(sprintf("ALTER TABLE %s ADD COLUMN test_period period", $this->object_name));
        $this->connection->getDatabase()->registerConverter('Period', new Converter\PgPeriod(), array('period'));
        $this->addField('test_period', 'Period');
    }
}

class TestConverter extends BaseObject
{
}

require __DIR__."/../../Pomm/External/lime.php";

$service = new Service();
$dsn = require "config.php";
$service->setDatabase('default', new Database(array('dsn' => $dsn, 'identity_mapper' => false)));

return $service;
