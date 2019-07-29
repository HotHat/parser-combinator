<?php
namespace Wow;

use Closure;

// class helper functions
function carrying($fun) {
    return new Carrying($fun);
}

function failure($reason) {
    return new Failure($reason);
}

function parser($fn) {
    return new Parser($fn);
}

function success($result, $remain) {
    return new Success($result, $remain);
}



// parser combinator

function satisfy(Closure $compare, string $label) {
    $result =  function($str) use ($compare, $label) {
        if (empty($str)) {
            return failure('Empty string');
        }
        $char = mb_substr($str, 0, 1);
        if ($compare($char)) {
            return success($char, mb_substr($str, 1));
        } else  {
            return failure(sprintf('Expect: %s, get: %s', $label, $char));
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
    $fun = $parser->FUN;
    return $fun($str);
}

function andThen(Parser $pa, Parser $pb) {
    $fun = function ($str) use ($pa, $pb) {
        $f1 = $pa->FUN;
        $res1 = $f1($str);
        if ($res1 instanceof Failure) {
            return $res1;
        }

        $f2 = $pb->FUN;
        $res2 =  $f2($res1->REMAIN);

        if ($res2 instanceof Failure) {
            return $res2;
        }

        return success([$res1->RESULT, $res2->RESULT], $res2->REMAIN);
    };

    return new Parser($fun);
}

function orThen($pA, $pB) {
    $fun = function ($str) use ($pA, $pB) {
        $f1 = $pA->FUN;
        $res1 = $f1($str);
        if ($res1 instanceof Success) {
            return $res1;
        }

        $f2 = $pB->FUN;
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


function anyOf($arr) {

    $arr =  array_map(function($i) {return pchar($i);}, $arr);

    return choice(...$arr);
}

function pstring($str) {
    $arr =  array_map(function($i) {return pchar($i);}, preg_split('//u', $str, null, PREG_SPLIT_NO_EMPTY));

    return sequence(...$arr);
}
function returnP($x) {
    $call =  function ($input) use($x) {
        return success($x, $input);
    };
    
    return new Parser($call);
}

function mapP(Carrying $fn, Parser $parser) {
    $call =  function($input) use ($fn, $parser) {
        $result = run($parser, $input);
        
        if ($result instanceof Success) {
            if (is_array($result->RESULT)) {
                $r = $fn->invoke(...$result->RESULT);
            } else {
                $r = $fn->invoke($result->RESULT);
            }
           
            return success($r, $result->REMAIN);
        } else  {
            return $result;
        }
    };
    return parser($call);
}

// can't understand
function applyP(Parser $fp, Parser $xp) {
    $p = andThen($fp, $xp);
    return mapP(carrying(function ($f, $x) {
        return $f->invoke($x);
    }), $p);
}

// lift a two parameter function to Parser World
function lift2($f, $xp, $yp) {
    return applyP(applyP(returnP($f), $xp), $yp);
}

function sequence(Parser ...$parsers) {
    if (empty($parsers)) {
        return returnP([]);
    }
    
    $first = $parsers[0];
    $other = array_slice($parsers, 1);
    
    $fn = function ($x, $y) {
        if (is_array($x)) {
            return $x[] = $y;
        }  else {
            return [$x, $y];
        }
    };
    
    return array_reduce($other, function($carry, $item) use ($fn) {
        return lift2($fn, $carry, $item);
    }, $first);
}


