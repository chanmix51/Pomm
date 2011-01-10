<?php

include dirname(__FILE__).'/../bootstrap/unit.php';

class PgLookWhereTest
{
  protected $test;
  protected $where;

  public function __construct()
  {
    $this->test = new lime_test();
  }

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
    $this->where = PgLookWhere::create($element, $values);

    return $this;
  }

  public function checkToString($expected)
  {
    $this->test->is((string) $this->where, $expected, sprintf('The parsed string matched "%s".', $expected));

    return $this;
  }

  public function checkConstructors($element = null)
  {
    $this->test->diag('Checking PgLookWhere constructors and element methods');

    $this->resetWhere($element);
    $this->test->isa_ok($this->where, 'PgLookWhere', 'PgLookWhere::create() returns a PgLookWhere instance');
    if (is_null($element))
    {
      $this->test->ok(!$this->where->hasElement(), 'Which has no elements');
    }
    else
    {
      $this->test->ok($this->where->hasElement(), 'Which has an element');
      $this->test->is($this->where->getElement(), $element, sprintf('Which is "%s"', $element));
    }

    return $this;
  }

  public function testParse()
  {
    $this->test->diag('Testing simple andWhere and orWhere calls and structure');

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

    $where = PgLookWhere::create('a')
      ->orWhere('b')
      ->andWhere('c');

    $this->orWhere($where)
      ->checkToString('(((A OR B OR C) AND D) OR ((a OR b) AND c))')
      ->resetWhere()
      ->orWhere($where)
      ->checkToString('((a OR b) AND c)')
      ->andWhere(PgLookWhere::create())
      ->checkToString('((a OR b) AND c)')
      ->resetWhere()
      ->andWhere(PgLookWhere::create())
      ->checkToString('true')
      ;

    return $this;
  }

  protected function testValues($good_values = array())
  {
    $this->test->is_deeply($this->where->getValues(), $good_values, 'Values are what is expected');

    return $this;
  }

  public function testGetValues()
  {
    $where = PgLookWhere::create('a', array('a'))
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

$my_test = new PgLookWhereTest();
$my_test->testParse()
  ->testGetValues()
  ;
