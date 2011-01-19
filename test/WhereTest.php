<?php
namespace Pomm;
use Pomm\Query\Where;

include __DIR__.'/../lib/External/lime.php';
include "autoload.php";

class WhereTest extends \lime_test
{
    protected $where;

    public function orWhere($element, $values = array())
    {
        $this->where->orWhere($element, $values);

        return $this;
    }

    public function andWhere($element, $values = array())
    {
        $this->where->andWhere($element, $values);

        return $this;
    }

    public function resetWhere($element = null, $values = array())
    {
        $this->where = Where::create($element, $values);

        return $this;
    }

    public function checkToString($expected)
    {
        $this->is((string) $this->where, $expected, sprintf('The parsed string matched "%s".', $expected));

        return $this;
    }

    public function checkConstructors($element = null)
    {
        $this->diag('Checking Where constructors and element methods');

        $this->resetWhere($element);
        $this->isa_ok($this->where, 'Pomm\Query\Where', 'Where::create() returns a Where instance');
        if (is_null($element))
        {
            $this->ok(!$this->where->hasElement(), 'Which has no elements');
        }
        else
        {
            $this->ok($this->where->hasElement(), 'Which has an element');
            $this->is($this->where->getElement(), $element, sprintf('Which is "%s"', $element));
        }

        return $this;
    }

    public function testParse()
    {
        $this->diag('Testing simple andWhere and orWhere calls and structure');

        $this->checkConstructors()
            ->checkToString('true')
            ->checkConstructors('A')
            ->checkToString('A')
            ->andWhere('B')
            ->checkToString('(A AND B)')
            ->andWhere('C')
            ->checkToString('(A AND B AND C)')
            ->orWhere('D')
            ->checkToString('((A AND B AND C) OR D)')
            ->orWhere('NOT E')
            ->checkToString('((A AND B AND C) OR D OR NOT E)')
            ->resetWhere('A')
            ->orWhere('B')
            ->checkToString('(A OR B)')
            ->orWhere('C')
            ->checkToString('(A OR B OR C)')
            ->andWhere('D')
            ->checkToString('((A OR B OR C) AND D)')
            ;

        $where = Where::create('a')
            ->orWhere('b')
            ->andWhere('c');

        $this->orWhere($where)
            ->checkToString('(((A OR B OR C) AND D) OR ((a OR b) AND c))')
            ->resetWhere()
            ->orWhere($where)
            ->checkToString('((a OR b) AND c)')
            ->andWhere(Where::create())
            ->checkToString('((a OR b) AND c)')
            ->resetWhere()
            ->andWhere(Where::create())
            ->checkToString('true')
            ;

        return $this;
    }

    protected function testValues($good_values = array())
    {
        $this->is_deeply($this->where->getValues(), $good_values, 'Values are what is expected');

        return $this;
    }

    public function testGetValues()
    {
        $where = Where::create('a', array('a'))
            ->andWhere('b', array('b', 'c'))
            ->orWhere('c', array('d', 'e', 'f'))
            ;

        $this->resetWhere('A', array(1))
            ->andWhere('B', array(2,3))
            ->andWhere('C', array(4,5,6))
            ->orWhere('D', array(7))
            ->orWhere('E', array(8,9))
            ->testValues(array(1,2,3,4,5,6,7,8,9))
            ->andWhere($where)
            ->testValues(array(1,2,3,4,5,6,7,8,9, 'a', 'b', 'c', 'd', 'e', 'f'))
            ;

        return $this;
    }

}

$my_test = new WhereTest();
$my_test->testParse()
    ->testGetValues()
    ;
