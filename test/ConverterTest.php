<?php

namespace Pomm\Test;

include __DIR__.'/../Pomm/External/lime.php';
include "autoload.php";
include "bootstrap.php";

use Pomm\Service;
use Pomm\Connection\Database;
use Pomm\Exception\Exception;
use Pomm\Type;

class converter_test extends \lime_test
{
    public 
        $object;

    protected 
        $service,
        $connection,
        $map;

    public function initialize()
    {
        $this->service = new Service(array('default' => array('dsn' => 'pgsql://greg/greg')));
        $this->connection = $this->service->createConnection();
        $this->map = $this->connection->getMapFor('Pomm\Test\TestConverter');
        $this->map->createTable();

        return $this;
    }

    public function __destruct()
    {
        $this->map->dropTable();
        parent::__destruct();
    }

    public function updateObject(Array $fields)
    {
        $this->map->updateOne($this->object, $fields);
    }

    public function createObject($values)
    {
        $this->object = $this->map->createObject();
        $this->object->hydrate($values);
    }

    public function testObject(Array $values)
    {
        foreach($values as $key => $value)
        {
            if (is_object($value))
            {
                $this->isa_ok($this->object[$key], get_class($value), sprintf("'%s' is an instance of '%s'.", $key, get_class($value)));
            }
            else
            {
                $this->is($this->object[$key], $values[$key], sprintf("Checking '%s'.", $key));
            }
        }
    }

    public function testBasics($values, $compare)
    {
        $this->info('basic types.');
        $this->object = $this->map->createObject();
        $this->object->hydrate($values);
        $this->map->saveOne($this->object);
        $this->ok(is_integer($this->object['id']), "'id' is an integer.");
        $this->is($this->object['id'], $compare['id'], sprintf("'id' is '%d'.", $compare['id']));
        $this->isa_ok($this->object['created_at'], 'DateTime', "'created_at' is a 'DateTime' instance.");
        $this->is($this->object['created_at']->format('d/m/Y H:i'), $compare['created_at']->format('d/m/Y H:i'), sprintf("'created_at' is '%s'.", $compare['created_at']->format('d/m/Y H:i')));
        $this->is($this->object['something'], $compare['something'], "'something' is 'plop'.");
        $this->ok(is_bool($this->object['is_true']), "'is_true' is boolean.");
        $this->is($this->object['is_true'], $compare['is_true'], sprintf("'is_true' is '%s'.", $compare['is_true'] ? 'true' : 'false'));
        $this->is($this->object['precision'], $compare['precision'], sprintf("'precision' match float '%f'.", $compare['precision']));
        $this->is($this->object['probed_data'], $compare['probed_data'], sprintf("'probed_data' match '%4.3f'.", $compare['probed_data']));
        $this->is(substr(base64_encode($this->object['binary_data']), 0, 20), substr(base64_encode($compare['binary_data']), 0, 20), "'binary_data' match.");
        $this->is($this->object['ft_search'], $compare['ft_search'], sprintf("'tsvector' field match '%s'.", $compare['ft_search']));

        return $this;
    }

    public function testPoint(\Pomm\Type\Point $point)
    {
        $this->info('\\Pomm\\Converter\\PgPoint');
        if (!$this->map->hasField('test_point'))
        {
            $this->info('Creating column test_point.');
            $this->map->addPoint();
        }

        $this->object->setTestPoint($point);
        $this->map->saveOne($this->object);

        $object = $this->map->findByPk($this->object->get($this->map->getPrimaryKey()));

        $this->ok(is_object($object['test_point']), "'point' is an object.");
        $this->ok($object['test_point'] instanceof \Pomm\Type\Point, "'point' is a \\Pomm\\Type\\Point instance.");
        $this->is($object['test_point']->x, $point->x, sprintf("Coord 'x' are equal (%f).", $point->x));
        $this->is($object['test_point']->y, $point->y, sprintf("Coord 'y' are equal (%f).", $point->y));

        return $this;
    }

    public function testLseg(\Pomm\Type\Segment $segment)
    {
        $this->info('\\Pomm\\Converter\\PgLseg');
        if (!$this->map->hasField('test_lseg'))
        {
            $this->info('Creating column test_lseg.');
            $this->map->addLseg();
        }

        $this->object->setTestLseg($segment);
        $this->map->updateOne($this->object, array('test_lseg'));

        $object = $this->map->findByPk($this->object->get($this->map->getPrimaryKey()));

        $this->ok(is_object($object['test_lseg']), "'test_lseg' is an object.");
        $this->ok($object['test_lseg'] instanceof \Pomm\Type\Segment, "'test_lseg' is a \\Pomm\\Type\\Segment instance.");
        $this->is($object['test_lseg']->point_a->x, $segment->point_a->x, sprintf("Coord 'x' are equal (%f).", $segment->point_a->x));
        $this->is($object['test_lseg']->point_a->y, $segment->point_a->y, sprintf("Coord 'y' are equal (%f).", $segment->point_a->y));
        $this->is($object['test_lseg']->point_b->x, $segment->point_b->x, sprintf("Coord 'x' are equal (%f).", $segment->point_b->x));
        $this->is($object['test_lseg']->point_b->y, $segment->point_b->y, sprintf("Coord 'y' are equal (%f).", $segment->point_b->y));

        return $this;
    }

    public function testHStore(Array $values)
    {
        $this->info('\\Pomm\\Converter\\PgHStore');
        if (!$this->map->hasField('test_hstore'))
        {
            $this->info('Creating column test_hstore.');
            $this->map->addHStore();
        }

        $this->object->setTestHstore($values);
        $this->map->updateOne($this->object, array('test_hstore'));

        $object = $this->map->findByPk($this->object->get($this->map->getPrimaryKey()));

        $hstore = $object['test_hstore'];
        foreach ($values as $key => $value)
        {
            if (is_null($value))
            {
                $this->ok(is_null($hstore[$key]), sprintf("Check hstore '%s' is NULL.", $key));
                print($hstore[$key]);
            }
            else
            {
                $this->is($hstore[$key], $value, sprintf("Check hstore key '%s' => '%s'.", $key, $value));
            }
        }

        return $this;
    }

    public function testCircle(Type\Circle $circle)
    {
        $this->info('\\Pomm\\Converter\\PgCircle');
        if (!$this->map->hasField('test_circle'))
        {
            $this->info('Creating column test_circle.');
            $this->map->addCircle();
        }

        $this->object->setTestCircle($circle);
        $this->map->updateOne($this->object, array('test_circle'));

        $object = $this->map->findByPk($this->object->get($this->map->getPrimaryKey()));
        $this->ok(is_object($object['test_circle']), "'test_circle' is an object.");
        $this->ok($object['test_circle'] instanceof \Pomm\Type\Circle, "'test_circle' is a \\Pomm\\Type\\Circle instance.");
        $this->is($object['test_circle']->center->x, $circle->center->x, sprintf("Center 'x' is equal to '%f'.", $circle->center->x));
        $this->is($object['test_circle']->center->y, $circle->center->y, sprintf("Center 'y' is equal to '%f'.", $circle->center->y));
        $this->is($object['test_circle']->radius, $circle->radius, sprintf("Radius is equal to '%f'.", $circle->radius));

        return $this;

    }

    public function testInterval(\DateInterval $interval)
    {
        $this->info('\\Pomm\\Converter\\PgInterval');
        if (!$this->map->hasField('test_interval'))
        {
            $this->info('Creating column test_interval.');
            $this->map->addInterval();
        }

        $this->object->setTestInterval($interval);
        $this->map->updateOne($this->object, array('test_interval'));

        $object = $this->map->findByPk($this->object->get($this->map->getPrimaryKey()));
        $this->ok(is_object($object['test_interval']), "'test_interval' is an object.");
        $this->ok($object['test_interval'] instanceof \DateInterval, "'test_interval' is a \\DateInterval instance.");
        $this->is($object['test_interval']->format('%Y %m %d %h %i %s'), $this->object['test_interval']->format('%Y %m %d %h %i %s'), "Formatted intervals match.");

        $this->object->setTestInterval(\DateInterval::createFromDateString("18 months"));
        $this->map->updateOne($this->object, array('test_interval'));
        $this->is($this->object['test_interval']->format('%Y years %m months'), "00 years 18 months", "Record NOT updated with database value.");

    }
}

$test = new converter_test();
$binary = file_get_contents('https://twimg0-a.akamaihd.net/profile_images/1583413811/smile_normal.JPG');

$test
    ->initialize()
    ->testBasics(array('something' => 'plop', 'is_true' => false, 'precision' => 0.123456789, 'probed_data' => 4.3210, 'binary_data' => $binary, 'ft_search' => "'academi':1 'battl':15 'canadian':20 'dinosaur':2 'drama':5 'epic':4 'feminist':8 'mad':11 'must':14 'rocki':21 'scientist':12 'teacher':17"), array('id' => 1, 'created_at' => new \DateTime(), 'something' => 'plop', 'is_true' => false, 'precision' => 0.123456789, 'probed_data' => 4.321, 'binary_data' => $binary, 'ft_search' => "'academi':1 'battl':15 'canadian':20 'dinosaur':2 'drama':5 'epic':4 'feminist':8 'mad':11 'must':14 'rocki':21 'scientist':12 'teacher':17"))
    ->testPoint(new Type\Point(0,0))
    ->testPoint(new Type\Point(47.123456,-0.654321))
    ->testLseg(new Type\Segment(new Type\Point(1,1), new Type\Point(2,2)))
    ->testHStore(array('plop' => 1, 'pika' => 'chu'))
    ->testHStore(array('a' => null, 'b' => 2))
    ->testCircle(new Type\Circle(new Type\Point(1,2), 3))
    ->testInterval(\DateInterval::createFromDateString('1 years 8 months 30 days 14 hours 25 minutes 7 seconds'))
    ;
