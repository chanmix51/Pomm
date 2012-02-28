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
            'things'           => 'String[]',
            'is_true'          => 'Boolean',
            'are_true'         => 'Boolean[]',
            'precision'        => 'Number',
            'precisions'       => 'Number[]',
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

            $sql = sprintf(<<<_
CREATE TABLE %s (
    id SERIAL PRIMARY KEY, 
    created_at TIMESTAMP NOT NULL DEFAULT now(), 
    something VARCHAR, 
    things VARCHAR[], 
    is_true BOOLEAN, 
    are_true BOOLEAN[], 
    precision FLOAT, 
    precisions FLOAT[], 
    probed_data NUMERIC(4,3), 
    binary_data BYTEA, 
    ft_search tsvector, 
    times TIMESTAMP[]
)
_
                , $this->object_name);
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
        $this->connection->getDatabase()->executeAnonymousQuery(sprintf("ALTER TABLE %s ADD COLUMN test_points point[]", $this->object_name));
        $this->connection->getDatabase()->registerConverter('Point', new Converter\PgPoint(), array('point'));
        $this->addField('test_point', 'Point');
        $this->addField('test_points', 'Point[]');
    }

    public function addLseg()
    {
        $this->connection->getDatabase()->executeAnonymousQuery(sprintf("ALTER TABLE %s ADD COLUMN test_lseg lseg", $this->object_name));
        $this->connection->getDatabase()->executeAnonymousQuery(sprintf("ALTER TABLE %s ADD COLUMN test_lsegs lseg[]", $this->object_name));
        $this->connection->getDatabase()->registerConverter('Lseg', new Converter\PgLseg(), array('lseg'));
        $this->addField('test_lseg', 'Lseg');
        $this->addField('test_lsegs', 'Lseg[]');
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
        $this->connection->getDatabase()->executeAnonymousQuery(sprintf("ALTER TABLE %s ADD COLUMN test_circles circle[]", $this->object_name));
        $this->connection->getDatabase()->registerConverter('Circle', new Converter\PgCircle(), array('circle'));
        $this->addField('test_circle', 'Circle');
        $this->addField('test_circles', 'Circle[]');
    }

    public function addInterval()
    {
        $this->connection->getDatabase()->executeAnonymousQuery(sprintf("ALTER TABLE %s ADD COLUMN test_interval interval", $this->object_name));
        $this->connection->getDatabase()->executeAnonymousQuery(sprintf("ALTER TABLE %s ADD COLUMN test_intervals interval[]", $this->object_name));
        $this->connection->getDatabase()->registerConverter('Interval', new Converter\PgInterval(), array('interval'));
        $this->addField('test_interval', 'Interval');
        $this->addField('test_intervals', 'Interval[]');
    }

    public function addXml()
    {
        $this->connection->getDatabase()->executeAnonymousQuery(sprintf("ALTER TABLE %s ADD COLUMN test_xml xml", $this->object_name));
        $this->connection->getDatabase()->executeAnonymousQuery(sprintf("ALTER TABLE %s ADD COLUMN test_xmls xml[]", $this->object_name));
        $this->addField('test_xml', 'String');
        $this->addField('test_xmls', 'String[]');
    }

}

class TestConverter extends BaseObject
{
}

class TestConverterContainerMap extends BaseObjectMap
{
    public function initialize()
    {
        $this->object_class =  'Pomm\Test\TestConverterContainer';
        $this->object_name  =  'pomm_test.test_converter_container';
        $this->field_definitions  = array(
            'id'               => 'Number',
            'test_converter'   => 'TestConverter',
        );
        $this->pk_fields    = array('id');

        $this->connection
            ->getDatabase()
            ->registerConverter('TestConverter',
                new \Pomm\Converter\PgEntity(
                    $this->connection->getDatabase(),
                    'Pomm\Test\TestConverter'),
                array('pomm_test.test_converter')
            );
    }

    public function createTable()
    {
        $sql = "CREATE TABLE %s (id serial primary key, test_converter %s NOT NULL)";
        $test_converter_map = $this->connection->getMapFor('Pomm\Test\TestConverter');

        $this->connection
            ->getDatabase()
            ->executeAnonymousQuery(sprintf($sql, $this->object_name, $test_converter_map->getTableName()),
                $this->getTableName(),
                $test_converter_map->getTableName());
    }
}

class TestConverterContainer extends BaseObject
{
}

require __DIR__."/../../Pomm/External/lime.php";

$service = new Service();
$dsn = require "config.php";
$service->setDatabase('default', new Database(array('dsn' => $dsn, 'identity_mapper' => false)));

return $service;
