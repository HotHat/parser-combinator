<?php declare(strict_types=1);

namespace Wow;


class ParserTest extends \PHPUnit\Framework\TestCase
{

    public function testCarrying()
    {
        $f = carrying(function ($x, $y, $z) {
            return $x + $y + $z;
        });

        $a = 1;
        $b = [2, 3];

        $f->invoke($a);
        //var_dump( $f->invoke($b));
        var_dump($f->invoke(...$b));

        $this->assertEquals(1, 1);
    }

    public function testPChar()
    {
        $a = pchar('a');

        $r = run($a, 'abc');

        $this->assertEquals(true, $r instanceof Success);
        $this->assertEquals('a', $r->RESULT);
    }

    public function testAndThen()
    {
        $pa = pchar('a');
        $pb = pchar('b');
        $pab = andThen($pa, $pb);

        $r1 = run($pab, 'abc');
        echo $r1, PHP_EOL;
        $this->assertEquals(true, $r1 instanceof Success);
        $r2 = run($pab, 'adc');
        echo $r2;
        $this->assertEquals(true, $r2 instanceof Failure);
    }

    public function testOrThen()
    {
        $pa = pchar('a');
        $pb = pchar('b');
        $paOrb = orThen($pa, $pb);
        $r1 = run($paOrb, 'abc');
        echo $r1, PHP_EOL;
        $this->assertEquals(true, $r1 instanceof Success);

        $r2 = run($paOrb, 'bdc');
        echo $r2, PHP_EOL;
        $this->assertEquals(true, $r2 instanceof Success);

        $r3 = run($paOrb, 'cdc');
        echo $r3, PHP_EOL;
        $this->assertEquals(true, $r3 instanceof Failure);
    }

    public function testChoice()
    {
        $pa = pchar('a');
        $pb = pchar('b');
        $choice = choice($pa, $pb);
        echo run($choice, 'abc'), PHP_EOL;
        echo run($choice, 'bdc'), PHP_EOL;
        echo run($choice, 'cdc'), PHP_EOL;
        $this->assertEquals(1, 1);
    }

    public function testAnyOf()
    {
        echo '---------test anyOf----------', PHP_EOL;
        $any = anyOf(['a', 'b', 'c']);
        echo run($any, 'abc'), PHP_EOL;
        echo run($any, 'bdc'), PHP_EOL;
        echo run($any, 'cdc'), PHP_EOL;
        echo run($any, 'ddc'), PHP_EOL;
        echo '---------test parse digit----------', PHP_EOL;
        $parseDigit = anyof(array_map(function ($i) {
            return (string)$i;
        }, range(0, 9)));
        echo run($parseDigit, '1928374'), PHP_EOL;
        echo run($parseDigit, 'bct'), PHP_EOL;
        $this->assertEquals(1, 1);
    }

    public function testThreeDigit()
    {
        $parseDigit = anyof(array_map(function ($i) {
            return (string)$i;
        }, range(0, 9)));
        echo run($parseDigit, '1928374'), PHP_EOL;
        echo run($parseDigit, 'bct'), PHP_EOL;
        echo '---------test parse three digit----------', PHP_EOL;
        $parseThreeDigits = andThen(andThen($parseDigit, $parseDigit), $parseDigit);
        echo run($parseThreeDigits, '123A'), PHP_EOL;
        echo run($parseThreeDigits, '12b'), PHP_EOL;
        $this->assertEquals(1, 1);
    }

    public function testMapP()
    {
        $fn = carrying(function ($param) {
            [[$a, $b], $c] = $param;
            return $a . $b . $c;
        });
        $parseDigit = anyof(array_map(function ($i) {
            return (string)$i;
        }, range(0, 9)));
        $parseThreeDigits = andThen(andThen($parseDigit, $parseDigit), $parseDigit);

        //$r1 = run($parseThreeDigits, '123A');
        $p = mapP($fn, $parseThreeDigits);
        //$p = mapP(function($x) {return intval($x);}, $p);
        $r1 = run($p, '123A');
        echo $r1, PHP_EOL;
        //$r1 = run($p, '123A');
        //echo $r1;
        $this->assertEquals(true, $r1 instanceof Success);
        $this->assertEquals('123', $r1->RESULT);

        $p2 = mapP(carrying(function ($x) {
            return intval($x);
        }), $p);
        $r2 = run($p2, '123A');
        echo $r2, PHP_EOL;
        $this->assertEquals(true, $r2 instanceof Success);
        $this->assertEquals(123, $r2->RESULT);

        //$this->assertEquals(123, $r1->RESULT);
        //echo run($p, '12b'), PHP_EOL;
    }

    public function testMapP2()
    {
        $fn = carrying(function ($param) {
            [[$a, $b], $c] = $param;
            return $a . $b . $c;
        });
        $parseDigit = anyof(array_map(function ($i) {
            return (string)$i;
        }, range(0, 9)));
        $parseThreeDigits = andThen(andThen($parseDigit, $parseDigit), $parseDigit);

        //$r1 = run($parseThreeDigits, '123A');
        $p = mapP2($fn, $parseThreeDigits);
        //$p = mapP(function($x) {return intval($x);}, $p);
        $r1 = run($p, '123A');
        echo $r1, PHP_EOL;
        //$r1 = run($p, '123A');
        //echo $r1;
        $this->assertEquals(true, $r1 instanceof Success);
        $this->assertEquals('123', $r1->RESULT);

        $p2 = mapP2(carrying(function ($x) {
            return intval($x);
        }), $p);
        $r2 = run($p2, '123A');
        echo $r2, PHP_EOL;
        $this->assertEquals(true, $r2 instanceof Success);
        $this->assertEquals(123, $r2->RESULT);

        //$this->assertEquals(123, $r1->RESULT);
        //echo run($p, '12b'), PHP_EOL;
    }

    public function testReturnP()
    {

        $this->assertEquals(1, 1);
    }

    public function testApplyP()
    {
        $fp = returnP(carrying(function ($x, $y, $z) {
            return $x + $y + $z;
        }));

        $p1 = pchar('1');
        $p2 = pchar('5');
        $p3 = pchar('3');
        $x = applyP($fp, $p1);
        $y = applyP($x, $p2);
        $z = applyP($y, $p3);

        $r = run($z, '153A');
        echo $r;
        $this->assertEquals(true, $r instanceof Success);
        $this->assertEquals(9, $r->RESULT);
    }

    public function testApplyP2()
    {
        $fp = returnP(carrying(function ($x, $y, $z) {
            return $x + $y + $z;
        }));

        $p1 = pchar('1');
        $p2 = pchar('5');
        $p3 = pchar('3');
        $x = applyP2($fp, $p1);
        $y = applyP2($x, $p2);
        $z = applyP2($y, $p3);

        $r = run($z, '153A');
        echo $r;
        $this->assertEquals(true, $r instanceof Success);
        $this->assertEquals(9, $r->RESULT);
    }

    public function testLift2()
    {
        $fp = carrying(function ($x, $y) {
            return $x + $y;
        });

        $xp = pchar('1');
        $yp = pchar('2');

        $x = lift2($fp, $xp, $yp);

        $r = run($x, '12A');
        echo $r;
        $this->assertEquals(true, $r instanceof Success);
        $this->assertEquals(3, $r->RESULT);
    }

    public function testSequence()
    {
        $p1 = pchar('1');
        $p2 = pchar('2');
        $p3 = pchar('3');
        $p4 = pchar('4');

        // $fn = carrying(function ($x, $y) {
        //     $x[] = $y;
        //     return $x ;
        // });

        $x = sequence([$p1, $p2, $p3, $p4]);
        // $x = lift2($fn, returnP([]), $p1);
        // $y = lift2($fn, $x, $p2);

        $r = run($x, '1234A');
        echo $r;

        $this->assertEquals(true, $r instanceof Success);
        $this->assertEquals(['1', '2', '3', '4'], $r->RESULT);
    }

    public function testPString() {

        $str = '1234';

        $p = pstring($str);

        $r = run($p, '1234A');
        echo $r;
        $this->assertEquals(true, $r instanceof Success);
        $this->assertEquals($str, $r->RESULT);

        $str = '你好世界';

        $p = pstring($str);

        $r = run($p, '你好世界1234A');
        echo $r;
        $this->assertEquals(true, $r instanceof Success);
        $this->assertEquals($str, $r->RESULT);

        $r = run($p, 'AB你好世界1234A');
        echo $r;
        $this->assertEquals(false, $r instanceof Success);
    }


    public function testMany() {
        $t1 = 'ABCD';
        $t2 = 'AACD';
        $t3 = 'AAAD';
        $t4 = '|BCD';

        $manyA = many(pchar('A'));

        $r1 = run($manyA, $t1);
        echo $r1 , PHP_EOL;
        $this->assertEquals(true, $r1 instanceof Success);

        $r2 = run($manyA, $t2);
        echo $r2 , PHP_EOL;
        $this->assertEquals(true, $r2 instanceof Success);

        $r3 = run($manyA, $t3);
        echo $r3 , PHP_EOL;
        $this->assertEquals(true, $r3 instanceof Success);

        $r4 = run($manyA, $t4);
        echo $r4 , PHP_EOL;
        $this->assertEquals(true, $r4 instanceof Success);

        $w = anyOf([' ', "\t", "\n"]);

        $pw = many($w);

        echo run($pw, 'ABC') , PHP_EOL;
        echo run($pw, ' ABC') , PHP_EOL;
        echo run($pw, "\tABC") , PHP_EOL;
        echo run($pw, "\t\nABC") , PHP_EOL;

    }

    public function testMany1() {
        $t1 = '1ABC';
        $t2 = '12BC';
        $t3 = '123C';
        $t4 = '1234';
        $t5 = 'ABC';

        $digit = anyof(array_map(function ($i) { return (string)$i; }, range(0, 9)));

        $digits = many1($digit);

        $r1 = run($digits, $t1);
        echo $r1 , PHP_EOL;
        $this->assertEquals(true, $r1 instanceof Success);

        $r2 = run($digits, $t2);
        echo $r2 , PHP_EOL;
        $this->assertEquals(true, $r2 instanceof Success);

        $r3 = run($digits, $t3);
        echo $r3 , PHP_EOL;
        $this->assertEquals(true, $r3 instanceof Success);

        $r4 = run($digits, $t4);
        echo $r4 , PHP_EOL;
        $this->assertEquals(true, $r4 instanceof Success);

        $r5 = run($digits, $t5);
        echo $r5 , PHP_EOL;
        $this->assertEquals(false, $r5 instanceof Success);

    }

    public function testOptional() {
        $pa = pchar('a');

        $optA = optional($pa);

        $r = run($optA, 'aA');

        echo $r;
        $this->assertEquals(true, $r instanceof Success);
        $this->assertEquals(true, $r->RESULT instanceof Some);
    }

    public function testKeepLeft() {
        $pa = pchar('a');
        $pb = pchar('b');

        $optA = keepLeft($pa, $pb);

        $r = run($optA, 'ab');

        echo $r, PHP_EOL;
        $this->assertEquals('a', $r->RESULT);

        $digit = anyof(array_map(function ($i) { return (string)$i; }, range(0, 9)));

        $digitThenSemicolon = keepLeft($digit, optional(pchar(';')));

        $r = run($digitThenSemicolon, '5;');
        echo $r;
        $this->assertEquals('5', $r->RESULT);
    }

    public function testKeepRight() {
        $pa = pchar('a');
        $pb = pchar('b');

        $optA = keepRight($pa, $pb);

        $r = run($optA, 'ab');

        echo $r;
        $this->assertEquals('b', $r->RESULT);

    }

    public function pint () {

        $digit = anyof(array_map(function ($i) { return (string)$i; }, range(0, 9)));

        $fn = carrying(function($x) {return intval(implode('', $x));});

        $digits = many1($digit);

        return mapP($fn, $digits);
    }

    public function testPint() {
        $pint = $this->pint();

        $r1 = run($pint, '1ABC');
        echo $r1, PHP_EOL;
        $this->assertEquals(true, $r1 instanceof Failure);

        $r2 = run($pint, '11BC');
        echo $r2, PHP_EOL;
        $this->assertEquals(true, $r2 instanceof Failure);

        $r3 = run($pint, '123C');
        echo $r3, PHP_EOL;
        $this->assertEquals(true, $r3 instanceof Failure);

        $r4 = run($pint, '1234');
        echo $r4, PHP_EOL;
        $this->assertEquals(true, $r4 instanceof Failure);


        $r5 = run($pint, 'ABCD');
        echo $r5, PHP_EOL;
        $this->assertEquals(true, $r5 instanceof Failure);
    }

    public function testBetween() {
        $pdoublequote = pchar('"');
        $pint = $this->pint();

        $quoteInteger = between($pdoublequote, $pint, $pdoublequote);

        $r = run ($quoteInteger, '"1234"');
        echo $r, PHP_EOL;
        $this->assertEquals('1234', $r->RESULT);

        $r = run ($quoteInteger, '1234');
        echo $r;
        $this->assertEquals(true, $r instanceof Failure);

    }

    public function testSepBy1() {
        $comma = pchar(',');
        $digit = anyof(array_map(function ($i) { return (string)$i; }, range(0, 9)));

        $oneOrMoreDigitList = sepBy1($digit, $comma);

        $r1 = run($oneOrMoreDigitList, '1;');
        echo $r1, PHP_EOL;
        $this->assertEquals(true, $r1 instanceof Success);

        $r2 = run($oneOrMoreDigitList, '1,2;');
        echo $r2, PHP_EOL;
        $this->assertEquals(true, $r2 instanceof Success);

        $r3 = run($oneOrMoreDigitList, '1,2,3;');
        echo $r3, PHP_EOL;
        $this->assertEquals(true, $r3 instanceof Success);

        $r4 = run($oneOrMoreDigitList, 'Z;');
        echo $r4, PHP_EOL;
        $this->assertEquals(true, $r4 instanceof Failure);
    }

    public function testSepBy() {
        $comma = pchar(',');
        $digit = anyof(array_map(function ($i) { return (string)$i; }, range(0, 9)));

        $zeroOrMoreDigitList = sepBy($digit, $comma);


        $r = run($zeroOrMoreDigitList, '1;');
        echo $r;

        $r2 = run($zeroOrMoreDigitList, '1,2,3,4;');
        echo $r2;

        $r3 = run($zeroOrMoreDigitList, 'Z1,2,3,4;');
        echo $r3;

        $this->assertEquals(1, 1);
    }

    public function testBindP()
    {
        $fn = carrying(function ($param) {
            [[$a, $b], $c] = $param;
            return $a . $b . $c;
        });
    
        $pa = pchar('a');
        $pb = pchar('b');
        $pc = pchar('c');
        $p = andThen(andThen($pa, $pb), $pc);
    
        //$r1 = run($parseThreeDigits, '123A');
        $mapP = function ($f, $p) {
            $fn = carrying(function ($x) use ($f) {
                return returnP($f->invoke($x));
            });
            return bindP($fn, $p);
        };
    
        // $p = mapP($fn, $p);
        $mp = $mapP($fn, $p);
        //$p = mapP(function($x) {return intval($x);}, $p);
        $r1 = run($mp, 'abc');
        echo $r1, PHP_EOL;
    
    
        // $map = bindP()
    }
}
