<?php
namespace Wow;

function satisfy(\Closure $compare, string $label) {
    $result =  function($str) use ($compare, $label) {
        if (empty($str)) {
            return failure('Empty string');
        }
        if ($compare($str[0])) {
            return success($label, substr($str, 1, mb_strlen($str) - 1));
        } else  {
            return failure(sprintf('Expect: %s, get: %s', $label, $str[0]));
        }
    };
    return new Parser($result);
}

function pchar($char) {
    return satisfy(function($a) use ($char) {
        return $a == $char;
    }, $char);
}

function run(Parser $parser, string $str) {
    $fun = $parser->get();
    return $fun($str);
}

function andThen($pA, $pB) {
    $fun = function ($str) use ($pA, $pB) {
        $f1 = $pA->get();
        $res1 = $f1($str);
        if ($res1 instanceof Failure) {
            return $res1;
        }

        $f2 = $pB->get();
        $res2 =  $f2($res1->getNext());

        if ($res2 instanceof Failure) {
            return $res2;
        }

        return success([$res1->getAst(), $res2->getAst()], $res2->getNext());
    };

    return new Parser($fun);
}

function orThen($pA, $pB) {
    $fun = function ($str) use ($pA, $pB) {
        $f1 = $pA->get();
        $res1 = $f1($str);
        if ($res1 instanceof Success) {
            return $res1;
        }

        $f2 = $pB->get();
        return $f2($str);
    };

    return new Parser($fun);
}

function choice(Parser ...$parsers) {
    assert(count($parsers) >= 1);
    $first = $parsers[0];
    $other = array_slice($parsers, 1);

    return array_reduce($other, function($carry, $item) {
        return orThen($carry, $item);
    }, $first);
}

function sequence(Parser ...$parsers) {
    assert(count($parsers) >= 1);
    $first = $parsers[0];
    $other = array_slice($parsers, 1);

    return array_reduce($other, function($carry, $item) {
        return andThen($carry, $item);
    }, $first);
}

function anyOf($arr) {

    $arr =  array_map(function($i) {return pchar($i);}, $arr);

    return choice(...$arr);
}

function pstring($str) {
    $arr =  array_map(function($i) {return pchar($i);}, preg_split('//u', $str, null, PREG_SPLIT_NO_EMPTY));

    return sequence(...$arr);
}

function mapP(\Closure $fn, Parser $parser) {
    $call =  function($input) use ($fn, $parser) {
        $result = run($parser, $input);

        if ($result instanceof Success) {
            [$f, $x] = $result->getAst();

            if ($f instanceof Carrying) {
                $r = $f->invoke($x);
            } else {
                assert('$f must be Carrying class');
            }
            return success($r, $result->getNext());
        } else  {
            return $result;
        }
    };
    return new Parser($call);
}
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