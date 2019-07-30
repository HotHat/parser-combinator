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
function satisfy(Closure $compare, string $label) : Parser {
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
    return parser($result);
}

function pchar($char) : Parser {
    return satisfy(function($a) use ($char) {
        return $a == $char;
    }, $char);
}

function run(Parser $parser, string $str) : Result {
    $fun = $parser->FUN;
    return $fun($str);
}

function andThen(Parser $pa, Parser $pb) : Parser {
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

    return parser($fun);
}

function orThen($pa, $pb) : Parser {
    $fun = function ($str) use ($pa, $pb) {
        $f1 = $pa->FUN;
        $res1 = $f1($str);
        if ($res1 instanceof Success) {
            return $res1;
        }

        $f2 = $pb->FUN;
        return $f2($str);
    };

    return parser($fun);
}

function choice(Parser ...$parsers) : Parser {
    assert(count($parsers) >= 1);
    $first = $parsers[0];
    $other = array_slice($parsers, 1);

    return array_reduce($other, function($carry, $item) {
        return orThen($carry, $item);
    }, $first);
}


function anyOf($arr) : Parser {

    $arr =  array_map(function($i) {return pchar($i);}, $arr);

    return choice(...$arr);
}

function returnP($x) : Parser {
    $call =  function ($input) use($x) {
        return success($x, $input);
    };
    
    return parser($call);
}

function mapP(Carrying $fn, Parser $parser) : Parser {
    $call =  function($input) use ($fn, $parser) {
        $result = run($parser, $input);
        
        if ($result instanceof Success) {
            // if (is_array($result->RESULT)) {
            //     $r = $fn->invoke(...$result->RESULT);
            // } else {
            $r = $fn->invoke($result->RESULT);
            // }
           
            return success($r, $result->REMAIN);
        } else  {
            return $result;
        }
    };
    return parser($call);
}

// can't understand
function applyP(Parser $fp, Parser $xp) : Parser {
    $p = andThen($fp, $xp);
    return mapP(carrying(function ($param) {
        [$f, $x] = $param;
        return $f->invoke($x);
    }), $p);
}

// lift a two parameter function to Parser World
function lift2($f, $xp, $yp) : Parser {
    return applyP(applyP(returnP($f), $xp), $yp);
}

function sequence(array $parsers) : Parser {

    $fn = carrying(function ($x, $y) {
        $x[] = $y;
        return $x;
    });
    
    return array_reduce($parsers, function($carry, $item) use ($fn) {
        return lift2($fn, $carry, $item);
    }, returnP([]));
}

function pstring($str) : Parser {
    $arr =  array_map(function($i) {
                return pchar($i);
            }, preg_split('//u', $str, null, PREG_SPLIT_NO_EMPTY));

    $fn = carrying(function ($x) {
        return implode('', $x);
    });

    return mapP($fn, sequence($arr));
}

function parseZeroOrMore(Parser $parser, $input) : array {
    $firstResult =  run($parser, $input);

    if ($firstResult instanceof Failure) {
        return [[], $input];
    }

    $result = [$firstResult->RESULT];

    $parseZeroOrMore = $firstResult;
    $remain = $firstResult->REMAIN;

    while ($parseZeroOrMore instanceof Success) {
        $parseZeroOrMore = run($parser, $remain);

        if ($parseZeroOrMore instanceof Success) {
            $result[] = $parseZeroOrMore->RESULT;
            $remain = $parseZeroOrMore->REMAIN;
        }
    }

    return [$result, $remain];
}

function many(Parser $parser) : Parser {
    $innerFn = function ($input) use ($parser) {
        $result = parseZeroOrMore($parser, $input);
        return success(...$result);
    };

    return parser($innerFn);
}

function many1(Parser $parser) : Parser {
    $fn = function ($input) use ($parser) {
        $firstResult = run($parser, $input);

        if ($firstResult instanceof Failure) {
            return $firstResult;
        }

        $more = parseZeroOrMore($parser, $firstResult->REMAIN);

        $result = [$firstResult->RESULT];
        $result = array_merge($result, $more[0]);

        return success($result, $more[1]);

    };

    return parser($fn);

}


