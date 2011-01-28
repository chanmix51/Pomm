<?php
namespace Pomm\Test;
use Pomm\Pomm;
use Pomm\Object\BaseObject;
use Pomm\Object\BaseObjectMap;
use Pomm\Object\Collection;

class TestTableMap extends BaseObjectMap
{
    protected function initialize()
    {
        $this->connection   = Pomm::getConnection();
        $this->object_class =  'Pomm\\Test\\TestTable';
        $this->object_name  =  'book';
        $this->field_definitions  = array(
            'id'               =>    'IntType',
            'created_at'       =>    'TimestampType',
            'title'            =>    'StrType',
            'authors'          =>    'ArrayType[StrType]',
            'is_available'     =>    'BoolType',
        );
        $this->pk_fields    = array('id');
    }

    public function createTable()
    {
        $sql = "CREATE TABLE book (id SERIAL PRIMARY KEY, created_at TIMESTAMP NOT NULL DEFAULT now(), title VARCHAR(256) NOT NULL, authors VARCHAR(255)[] NOT NULL, is_available BOOLEAN NOT NULL DEFAULT true)";
        Pomm::executeAnonymousQuery($sql);
    }

    public function dropTable()
    {
        $sql = "DROP TABLE book";
        Pomm::executeAnonymousQuery($sql);
    }
}

class TestTable extends BaseObject
{
}
