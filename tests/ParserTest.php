<?php declare(strict_types=1);

namespace Wow;


class ParserTest extends \PHPUnit\Framework\TestCase {

    public function testCarrying() {
        $f = carrying(function ($x, $y, $z) {
            return $x + $y + $z;
        });
        
        $a = 1;
        $b = [2, 3];
        
        $f->invoke($a);
        //var_dump( $f->invoke($b));
        var_dump( $f->invoke(...$b));
        
    }

    public function testPChar() {
        $a = pchar('a');

        $r = run($a, 'abc');

        $this->assertEquals(true, $r instanceof Success);
        $this->assertEquals('a', $r->RESULT);
    }
    
    public function testAndThen() {
        $pa = pchar('a');
        $pb = pchar('b');
        $pab = andThen($pa, $pb);
    
        $r1 =  run($pab, 'abc');
        echo $r1;
        $this->assertEquals(true, $r1 instanceof Success);
        $r2 = run($pab, 'adc');
        echo $r2;
        $this->assertEquals(true, $r2 instanceof Failure);
    }
    
    public function testOrThen() {
        $pa = pchar('a');
        $pb = pchar('b');
        $paOrb = orThen($pa, $pb);
        $r1 =  run($paOrb, 'abc');
        $this->assertEquals(true, $r1 instanceof Success);
        
        $r2 = run($paOrb, 'bdc');
        $this->assertEquals(true, $r2 instanceof Success);
        
        $r3 = run($paOrb, 'cdc');
        $this->assertEquals(true, $r3 instanceof Failure);
    }
    
    public function testChoice() {
        $pa = pchar('a');
        $pb = pchar('b');
        $choice = choice($pa, $pb);
        echo run($choice, 'abc'), PHP_EOL;
        echo run($choice, 'bdc'), PHP_EOL;
        echo run($choice, 'cdc'), PHP_EOL;
    }
    
    public function testAnyOf() {
        echo '---------test anyOf----------', PHP_EOL;
        $any = anyOf(['a', 'b', 'c']);
        echo run($any, 'abc'), PHP_EOL;
        echo run($any, 'bdc'), PHP_EOL;
        echo run($any, 'cdc'), PHP_EOL;
        echo run($any, 'ddc'), PHP_EOL;
        echo '---------test parse digit----------', PHP_EOL;
        $parseDigit = anyof(array_map(function($i){return (string)$i;}, range(0, 9)));
        echo run($parseDigit, '1928374'), PHP_EOL;
        echo run($parseDigit, 'bct'), PHP_EOL;
    }
    
    public function testThreeDigit() {
        $parseDigit = anyof(array_map(function($i){return (string)$i;}, range(0, 9)));
        echo run($parseDigit, '1928374'), PHP_EOL;
        echo run($parseDigit, 'bct'), PHP_EOL;
        echo '---------test parse three digit----------', PHP_EOL;
        $parseThreeDigits = andThen(andThen($parseDigit, $parseDigit), $parseDigit);
        echo run($parseThreeDigits, '123A'), PHP_EOL;
        echo run($parseThreeDigits, '12b'), PHP_EOL;
    }
    
    public function testMapP() {
        $fn = carrying(function ($param) {
            [[$a, $b], $c] = $param;
            return $a . $b. $c;
        });
        $parseDigit = anyof(array_map(function($i){return (string)$i;}, range(0, 9)));
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
        
        $p2 = mapP(carrying(function($x) { return intval($x);}), $p);
        $r2 = run($p2, '123A');
        echo $r2, PHP_EOL;
        $this->assertEquals(true, $r2 instanceof Success);
        $this->assertEquals(123, $r2->RESULT);
        
        //$this->assertEquals(123, $r1->RESULT);
        //echo run($p, '12b'), PHP_EOL;
    }
    
    public function testReturnP() {
    
    }
    
    public function testApplyP() {
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
    
    public function testLift2() {
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
    
/*
 // --- test

//echo pchar('a', 'abc');
//echo pchar('b', 'abc');

// test pchar / run
echo '---------test pchar/run----------', PHP_EOL;
echo run(pchar('a'), 'abc'), PHP_EOL;
echo run(pchar('b'), 'abc'), PHP_EOL;


// test andThen
echo '---------test andThen----------', PHP_EOL;
$pa = pchar('a');
$pb = pchar('b');
$pab = andThen($pa, $pb);

echo run($pab, 'abc'), PHP_EOL;
echo run($pab, 'adc'), PHP_EOL;

// test orThen
echo '---------test orThen----------', PHP_EOL;
$paOrb = orThen($pa, $pb);
echo run($paOrb, 'abc'), PHP_EOL;
echo run($paOrb, 'bdc'), PHP_EOL;
echo run($paOrb, 'cdc'), PHP_EOL;

// test choice
echo '---------test choice----------', PHP_EOL;
$choice = choice($pa, $pb);
echo run($choice, 'abc'), PHP_EOL;
echo run($choice, 'bdc'), PHP_EOL;
echo run($choice, 'cdc'), PHP_EOL;

// test anyOf
echo '---------test anyOf----------', PHP_EOL;
$any = anyOf(['a', 'b', 'c']);
echo run($any, 'abc'), PHP_EOL;
echo run($any, 'bdc'), PHP_EOL;
echo run($any, 'cdc'), PHP_EOL;
echo run($any, 'ddc'), PHP_EOL;
echo '---------test parse digit----------', PHP_EOL;
$parseDigit = anyof(array_map(function($i){return (string)$i;}, range(0, 9)));
echo run($parseDigit, '1928374'), PHP_EOL;
echo run($parseDigit, 'bct'), PHP_EOL;

echo '---------test parse three digit----------', PHP_EOL;
$parseThreeDigits = andThen(andThen($parseDigit, $parseDigit), $parseDigit);
echo run($parseThreeDigits, '123A'), PHP_EOL;
echo run($parseThreeDigits, '12b'), PHP_EOL;

$fn = function ($param) {
    [[$a, $b], $c] = $param;
    return $a . $b. $c;
};
//$p = mapP($fn, $parseThreeDigits);
//$p = mapP(function($x) {return intval($x);}, $p);
//echo run($p, '123A'), PHP_EOL;
//echo run($p, '12b'), PHP_EOL;



// test pstring
echo '---------test pstring ----------', PHP_EOL;
$pAbc = pstring('abc');
echo run($pAbc, 'abcdef'), PHP_EOL;
echo run($pAbc, 'adcdef'), PHP_EOL;




function returnP($x) {
    $call =  function ($input) use($x) {
        return success($x, $input);
    };

    return new Parser($call);
}

// can't understand
function applyP(Parser $fp, Parser $xp) {
    $p = andThen($fp, $xp);
    return mapP(function ($f, $x) { return $f($x);}, $p);
}

$fp = returnP(carrying(function ($x, $y, $z) {
    return $x + $y + $z;
}));

$p1 = pchar('1');
$p2 = pchar('5');
$p3 = pchar('3');
$x = applyP($fp, $p1);
$y = applyP($x, $p2);
$z = applyP($y, $p3);

echo '---- apply test' .PHP_EOL;
echo run($z, '153') . PHP_EOL;
 */
}
