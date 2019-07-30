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
            return failure(sprintf('Failure Expecting: %s. Got: %s', $label, $char));
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

function optional(Parser $parser) {
    $some = mapP(carrying(function($x) {return new Some($x);}), $parser);
    $none = returnP(new None());

    return orThen($some, $none);
}

function keepLeft(Parser $left, Parser $right) {
    $p = andThen($left, $right);

    return mapP(carrying(function($param) {return $param[0];}), $p);
}

function keepRight(Parser $left, Parser $right) {
    $p = andThen($left, $right);

    return mapP(carrying(function($param) {return $param[1];}), $p);
}


function between(Parser $p1, Parser $p2, Parser $p3) {
    return keepLeft(keepRight($p1, $p2), $p3);
}

function sepBy1(Parser $p, Parser $sep) {
    $sepThenP = keepRight($sep, $p);

    $p = andThen($p, many($sepThenP));

    $fn  = carrying(function ($x) {
        return array_merge([$x[0]],  $x[1]);
    });

    return mapP($fn, $p);
}

function sepBy(Parser $p, Parser $sep) {
    return orThen(sepBy1($p, $sep), returnP([]));
}


function bindP(Carrying $fn, Parser $parser) {
    $fun = function ($input) use ($fn, $parser) {
        $f1 = $parser->FUN;
        $res1 = $f1($input);
        if ($res1 instanceof Failure) {
            return $res1;
        }

        $p2 = $fn->invoke($res1->RESULT);

        return run($p2, $res1->REMAIN);
    };

    return parser($fun);
}

// bindP version
function mapP2(Carrying $fn, Parser $parser) {
    $f = carrying(function($x) use ($fn) {return returnP($fn->invoke($x));});
    return bindP($f, $parser);
}

// bindP version
function applyP2(Parser $fp, Parser $xp) {
    $fn = carrying(function ($f) use ($xp) {
       // // $f2 = carrying(function ($x) use ($f) { return returnP($f->invoke($x));});
       // return bindP($f2, $xp);
        return mapP2($f, $xp);
    });

    return bindP($fn, $fp);
}
