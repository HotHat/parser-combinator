<?php

namespace Wow\Json;

use IntlChar;
use Wow\Some;

use function Wow\{
    manyChars,
    manyChars1,
    optional,
    pchar,
    choice,
    keepRight,
    orThen,
    pstring,
    satisfy,
    setLabel,
    mapP,
    anyOf,
    keepLeft,
    andThen
};

class JsonParser
{

    public function returnObject($obj) {
        return function($param) use ($obj) {
            return $obj;
        };
    }

    public function jNull() {

        return setLabel(mapP($this->returnObject(new JsonNull()), pstring("null")), 'null');
    }

    public function jBool() {
        $jtrue = mapP($this->returnObject(new JsonBool(true)), pstring('true'));
        $jfalse = mapP($this->returnObject(new JsonBool(false)), pstring('false'));

        return setLabel(orThen($jtrue, $jfalse), 'bool');
    }

    public function jUnescapedChar() {
        $label = 'char';
        return satisfy(function ($ch) { return $ch != '\\' && $ch != '"';}, $label);
    }

    public function jEscapedChar() {
        $map = [
            ['\\"', '"'],
            ['\\\\', '\\'],
            ['\\/', '/'],
            ['\\b', "\b"],
            ['\\f', "\f"],
            ['\\n', "\n"],
            ['\\r', "\r"],
            ['\\t', "\t"],
        ];
        $parr = array_map(function($item) {
            $parser = pstring($item[0]);
            $fn = function ($param) use ($item) {return $item[1];};
            return mapP($fn, $parser);
        }, $map);

        return setLabel(choice($parr), 'escaped char');
    }

    public function jUnicodeChar() {
        $backslash = pchar('\\');
        $uChar = pchar('u');
        $hexdigit = anyOf(['0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
                            'A', 'B', 'C', 'D', 'E', 'F', 'a', 'b', 'c', 'd', 'e', 'f']);

        $convertToChar = function ($param) {
            [[[$h1,$h2], $h3], $h4] = $param;
            return sprintf('\u%s%s%s%s', $h1, $h2, $h3, $h4);
        };
        $parser = andThen(andThen(andThen(keepRight(keepRight($backslash, $uChar), $hexdigit), $hexdigit), $hexdigit), $hexdigit);
        return mapP($convertToChar, $parser);

    }

    public function jString() {
        $quote = setLabel(pchar('"'), 'quote');
        $jchar = orThen(orThen($this->jUnescapedChar(), $this->jEscapedChar()), $this->jUnicodeChar());

        $parser = keepLeft(keepRight($quote, manyChars($jchar)), $quote);
        $fun = function($str) {return new JsonString($str);};

        return setLabel(mapP($fun, $parser), "quoted string");
    }

    public function jNumber() {
        $optSign = optional(pchar('-'));

        $zero = pstring('0');

        $digitOneNine = satisfy(function($ch) { return IntlChar::isdigit($ch) && $ch != '0';}, '1-9');

        $digit = satisfy(function($ch) { return IntlChar::isdigit($ch)}, 'digit');

        $point = pchar('.');

        $e = orThen(pchar('e'), pchar('E'));

        $optPlusMinus = optional(orThen(pchar('-'), pchar('+')));

        $nonZeroInt = mapP(function($first) {
            return function($rest) use ($first) {
                return $first . $rest;
            };
        }, andThen($digitOneNine, manyChars($digit)));

        $intPart = orThen($zero, $nonZeroInt);

        $fractionPart = keepRight($point, manyChars1($digit));

        $exponentPart = andThen(keepRight($e, $optPlusMinus), manyChars1($digit));


        $parser = andThen(andThen(andThen($optSign, $intPart), optional($fractionPart)), optional($exponentPart));

        $opt = function ($maybe) {
            if ($maybe instanceof Some) {
                return $$maybe->val;
            }
            return '';
        };

        $convertToJNumber = function ($param) use ($opt) {
            [[[$optSign, $intPart], $fractionPart], $expPart] = $param;

            $signStr = $opt($optSign);
            $f = $opt($fractionPart);
            $fractionPartStr = $f == '' ? '' : '.' . $f;



        };

        return setLabel(mapP($convertToJNumber, $parser), 'number');


    }


}