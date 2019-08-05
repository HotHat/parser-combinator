<?php

namespace Wow\Json;

use Closure;
use IntlChar;
use Wow\ParserPosition;
use Wow\Some;
use Wow\Parser;

use function Wow\{failure,
    parser,
    manyChars,
    between,
    manyChars1,
    optional,
    pchar,
    choice,
    keepRight,
    orThen,
    pstring,
    runOnInput,
    satisfy,
    sepBy1,
    setLabel,
    mapP,
    anyOf,
    keepLeft,
    andThen,
    spaces1,
    spaces};

class JsonParser
{
    private $jValue;
    public function __construct() {

        $this->jValue = parser(function($input) { echo 'Error Happened!', PHP_EOL, die();}, 'unknown');
        $this->jValueInit();
    }

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

    public function quotedString() {
        $quote = setLabel(pchar('"'), 'quote');
        $jchar = orThen(orThen($this->jUnescapedChar(), $this->jEscapedChar()), $this->jUnicodeChar());

        $parser = keepLeft(keepRight($quote, manyChars($jchar)), $quote);

        return $parser;
    }

    public function jString() {
        $quoteString = $this->quotedString();
        $fun = function($str) {return new JsonString($str);};

        return setLabel(mapP($fun, $quoteString), "quoted string");
    }

    public function jNumber() {
        $optSign = optional(pchar('-'));

        $zero = pstring('0');

        $digitOneNine = satisfy(function($ch) { return IntlChar::isdigit($ch) && $ch != '0';}, '1-9');

        $digit = satisfy(function($ch) { return IntlChar::isdigit($ch);}, 'digit');

        $point = pchar('.');

        $e = orThen(pchar('e'), pchar('E'));

        $optPlusMinus = optional(orThen(pchar('-'), pchar('+')));

        $nonZeroInt = mapP(function($param) {
            [$first, $rest] = $param;
            return $first . $rest;
        }, andThen($digitOneNine, manyChars($digit)));

        $intPart = orThen($zero, $nonZeroInt);

        $fractionPart = keepRight($point, manyChars1($digit));

        $exponentPart = andThen(keepRight($e, $optPlusMinus), manyChars1($digit));


        $parser = andThen(andThen(andThen($optSign, $intPart), optional($fractionPart)), optional($exponentPart));

        $opt = function ($maybe) {
            if ($maybe instanceof Some) {
                return $maybe->val;
            }
            return '';
        };

        $convertToJNumber = function ($param) use ($opt) {
            [[[$optSign, $intPart], $fractionPart], $expPart] = $param;

            $signStr = $opt($optSign);
            $f = $opt($fractionPart);
            $fractionPartStr = $f == '' ? '' : '.' . $f;

            $exp = $opt($expPart);

            if ($exp == '') {
                $expPartStr = '';
            } else {
                [$exOptSign, $exDigits] =  $exp;
                $expPartStr = 'e' . $opt($exOptSign) . $exDigits;
            }

            return new JsonNumber(floatval($signStr . $intPart . $fractionPartStr . $expPartStr));
        };

        return setLabel(mapP($convertToJNumber, $parser), 'number');


    }

    public function jNumber_() {
        return keepLeft($this->jNumber(), spaces1());
    }

    public function jArray() {
        $left = keepLeft(pchar('['), spaces());
        $right = keepLeft(pchar(']'), spaces());
        $comma = keepLeft(pchar(','), spaces());

        $jValue = $this->parseRef();
        $value = keepLeft($jValue, spaces());

        $values = sepBy1($value, $comma);

        $bt = between($left, $values, $right);
        $fn = function ($param) {
            return new JsonArray($param);
        };

        return setLabel(mapP($fn, $bt), 'array');
    }

    private function forwardRef($input) {
        return runOnInput($this->jValue, $input);
    }

    private function parseRef() {
        return parser(Closure::fromCallable([$this, 'forwardRef']), 'unknown');
    }

    public function jObject() {
        $left = keepLeft(pchar('{'), spaces());
        $right = keepLeft(pchar('}'), spaces());
        $colon = keepLeft(pchar(':'), spaces());
        $comma = keepLeft(pchar(','), spaces());
        $key = keepLeft($this->quotedString(), spaces());

        $jValue = $this->parseRef();
        $value = keepLeft($jValue, spaces());
        $keyValue = andThen(keepLeft($key, $colon), $value);
        $keyValues = sepBy1($keyValue, $comma);


        $bt = between($left, $keyValues, $right);
        $fn = function ($param) {
            $result = [];
            foreach ($param as $item) {
                $result[$item[0]] = $item[1];
            }

            return new JsonObject($result);
        };

        return setLabel(mapP($fn, $bt), 'object');

    }

    public function jValueInit() {
        $null = $this->jNull();
        $bool = $this->jBool();
        $number = $this->JNumber();
        $string = $this->jString();
        $array = $this->jArray();
        $object = $this->jObject();

        $this->jValue =choice([
            $null,
            $bool,
            $number,
            $string,
            $array,
            $object
        ]);
    }


}