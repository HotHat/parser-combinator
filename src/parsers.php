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
    $fun = $parser->FUN;
    return $fun($str);
}

function andThen($pA, $pB) {
    $fun = function ($str) use ($pA, $pB) {
        $f1 = $pA->FUN;
        $res1 = $f1($str);
        if ($res1 instanceof Failure) {
            return $res1;
        }

        $f2 = $pB->FUN;
        $res2 =  $f2($res1->RESULT);

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

function mapP(Closure $fn, Parser $parser) {
    $call =  function($input) use ($fn, $parser) {
        $result = run($parser, $input);

        if ($result instanceof Success) {
            [$f, $x] = $result->RESULT;

            if ($f instanceof Carrying) {
                $r = $f->invoke($x);
            } else {
                assert('$f must be Carrying class');
            }
            return success($r, $result->REMAIN);
        } else  {
            return $result;
        }
    };
    return parser($call);
}
