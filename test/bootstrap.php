<?php

namespace Pomm\Test;

use Pomm\Service;
use Pomm\Object\BaseObject;
use Pomm\Object\BaseObjectMap;
use Pomm\Object\Collection;
use Pomm\Converter;
use Pomm\Connection\Database;

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
        $sql = "CREATE SCHEMA pomm_test;";
        $this->query($sql);
        $sql = "CREATE TABLE pomm_test.book (id SERIAL PRIMARY KEY, created_at TIMESTAMP NOT NULL DEFAULT now(), last_out TIMESTAMP, last_in TIMESTAMP, title VARCHAR(256) NOT NULL, authors VARCHAR(255)[] NOT NULL, is_available BOOLEAN NOT NULL DEFAULT true, location POINT)";
        $this->query($sql);
    }

    public function dropTable()
    {
        $sql = "DROP SCHEMA pomm_test CASCADE;";
        $this->query($sql);
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
        );
        $this->pk_fields    = array('id');
    }

    public function createTable()
    {
        try
        {
            $this->connection->begin();
            $sql = "CREATE SCHEMA pomm_test";
            $this->query($sql);

            $sql = "CREATE TABLE pomm_test.converter (id SERIAL PRIMARY KEY, created_at TIMESTAMP NOT NULL DEFAULT now(), something VARCHAR, is_true BOOLEAN, precision FLOAT, probed_data NUMERIC(4,3), binary_data BYTEA, ft_search tsvector)";
            $this->query($sql);
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
        $sql = "DROP SCHEMA pomm_test CASCADE";
        $this->query($sql);
    }

    public function addPoint()
    {
        $this->query('ALTER TABLE pomm_test.converter ADD COLUMN test_point point');
        $this->connection->getDatabase()->registerConverter('Point', new Converter\PgPoint(), array('point'));
        $this->addField('test_point', 'Point');
    }

    public function addLseg()
    {
        $this->query('ALTER TABLE pomm_test.converter ADD COLUMN test_lseg lseg');
        $this->connection->getDatabase()->registerConverter('Lseg', new Converter\PgLseg(), array('lseg'));
        $this->addField('test_lseg', 'Lseg');
    }

    public function addHStore()
    {
        $this->query('ALTER TABLE pomm_test.converter ADD COLUMN test_hstore hstore');
        $this->connection->getDatabase()->registerConverter('HStore', new Converter\PgHStore(), array('hstore'));
        $this->addField('test_hstore', 'HStore');
    }

    public function addCircle()
    {
        $this->query('ALTER TABLE pomm_test.converter ADD COLUMN test_circle circle');
        $this->connection->getDatabase()->registerConverter('Circle', new Converter\PgCircle(), array('circle'));
        $this->addField('test_circle', 'Circle');
    }

    public function addInterval()
    {
        $this->query('ALTER TABLE pomm_test.converter ADD COLUMN test_interval interval');
        $this->connection->getDatabase()->registerConverter('Interval', new Converter\PgInterval(), array('interval'));
        $this->addField('test_interval', 'Interval');
    }

    public function addXml()
    {
        $this->query('ALTER TABLE pomm_test.converter ADD COLUMN test_xml xml');
        $this->addField('test_xml', 'String');
    }
}

class TestConverter extends BaseObject
{
}

$service = new Service();
$service->setDatabase('default', new Database(array('dsn' => 'pgsql://greg/greg', 'identity_mapper' => false)));

return $service;
