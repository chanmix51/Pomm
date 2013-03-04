<?php

namespace Pomm\Test\Query;

use Pomm\Query\Where;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructors()
    {
        $where = Where::create('A', array('1'));
        $this->assertInstanceOf('Pomm\Query\Where', $where,  'Where::create() returns a Where instance.');
        $this->assertTrue($where->hasElement(), 'Where instance has element.');
        $this->assertEquals('A', (string) $where, "String where is 'A'.");
        $this->assertEquals(array(1), $where->getValues(), 'Where values are "[1]".');

        $where = Where::createWhereIn('B', array(1, 2, 3));
        $this->assertInstanceOf('Pomm\Query\Where', $where,  'Where::create() returns a Where instance.');
        $this->assertTrue($where->hasElement(), 'Where instance has element.');
        $this->assertEquals('B IN (?, ?, ?)', (string) $where, "String where is 'B IN (?, ?, ?)'.");
        $this->assertEquals(array(1,2,3), $where->getValues(), 'Where values are "[1,2,3]".');

        $where = Where::create();
        $this->assertInstanceOf('Pomm\Query\Where', $where,  'Where::create() returns a Where instance');
        $this->assertFalse($where->hasElement(), 'Where instance has no elements.');
        $this->assertEquals(array(), $where->getValues(), 'And no values.');

        $where = Where::createWhereIn('(C, D)', array(array('pika', 1), array('chu', 2)));
        $this->assertEquals('(C, D) IN ((?, ?), (?, ?))', (string) $where, "String where is '(C, D) IN ((?, ?), (?, ?))'.");
        $this->assertEquals(array('pika', 1, 'chu', 2), $where->getValues(), 'Where values are "[pika, 1, chu, 2]".');

        return $where;
    }

    protected function checkToString($where, $expected_string, $expected_values)
    {
        $this->assertEquals($expected_string, (string) $where, sprintf("Where equals '%s'.", $expected_string));
        $this->assertEquals($expected_values, $where->getValues(), sprintf("Where values are ok."));
    }

    public function testParse()
    {
        $where = Where::create();
        $this->checkToString($where, 'true', array());
        $where1 = clone $where;
        $where1->andWhere('A', array(1));
        $this->checkToString($where1, 'A', array(1));
        $where1->andWhere('B', array(2));
        $this->checkToString($where1, '(A AND B)', array(1, 2));
        $where1->andWhere('C', array(3));
        $this->checkToString($where1, '(A AND B AND C)', array(1, 2, 3));
        $where1->orWhere('D', array(4));
        $this->checkToString($where1, '((A AND B AND C) OR D)', array(1, 2, 3, 4));
        $where1->orWhere('NOT E', array(5));
        $this->checkToString($where1, '((A AND B AND C) OR D OR NOT E)', array(1, 2, 3, 4, 5));

        $where2 = clone $where;
        $where2->orWhere('A', array(1));
        $this->checkToString($where2, 'A', array(1));
        $where2->orWhere('B', array(2));
        $this->checkToString($where2, '(A OR B)', array(1, 2));
        $where2->orWhere('C', array(3));
        $this->checkToString($where2, '(A OR B OR C)', array(1, 2, 3));
        $where2->andWhere('D', array(4));
        $this->checkToString($where2, '((A OR B OR C) AND D)', array(1, 2, 3, 4));
        $where2->andWhere('NOT E', array(5));
        $this->checkToString($where2, '((A OR B OR C) AND D AND NOT E)', array(1, 2, 3, 4, 5));

        $where->andWhere($where1);
        $this->checkToString($where, '((A AND B AND C) OR D OR NOT E)', array(1, 2, 3, 4, 5));
        $where->orWhere($where2);
        $this->checkToString($where, '((A AND B AND C) OR D OR NOT E OR ((A OR B OR C) AND D AND NOT E))', array(1, 2, 3, 4, 5, 1, 2, 3, 4, 5));
        $where->andWhere(Where::create())
            ->orWhere(Where::create());
        $this->checkToString($where, '((A AND B AND C) OR D OR NOT E OR ((A OR B OR C) AND D AND NOT E))', array(1, 2, 3, 4, 5, 1, 2, 3, 4, 5));
    }
}
