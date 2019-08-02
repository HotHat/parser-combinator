<?php
/**
 * Created by cncn.com.
 * User: lyhux
 * Date: 2019/8/2
 * Time: 9:47
 *　　　　　　　　┏┓　　　┏┓+ +
 *　　　　　　　┏┛┻━━━┛┻┓ + +
 *　　　　　　　┃　　　　　　　┃ 　
 *　　　　　　　┃　　　━　　　┃ ++ + + +
 *　　　　　　 ████━████ ┃+
 *　　　　　　　┃　　　　　　　┃ +
 *　　　　　　　┃　　　┻　　　┃
 *　　　　　　　┃　　　　　　　┃ + +
 *　　　　　　　┗━┓　　　┏━┛
 *　　　　　　　　　┃　　　┃　　　　　　　　　　　
 *　　　　　　　　　┃　　　┃ + + + +
 *　　　　　　　　　┃　　　┃　　　　Code is far away from bug with the animal protecting　　　　　　　
 *　　　　　　　　　┃　　　┃ + 　　　　神兽保佑,代码无bug　　
 *　　　　　　　　　┃　　　┃
 *　　　　　　　　　┃　　　┃　　+　　　　　　　　　
 *　　　　　　　　　┃　 　　┗━━━┓ + +
 *　　　　　　　　　┃ 　　　　　　　┣┓
 *　　　　　　　　　┃ 　　　　　　　┏┛
 *　　　　　　　　　┗┓┓┏━┳┓┏┛ + + + +
 *　　　　　　　　　　┃┫┫　┃┫┫
 *　　　　　　　　　　┗┻┛　┗┻┛+ + + +
 */

namespace Wow\Json;

use function Wow\{manyChars,
    pchar,
    carrying,
    choice,
    keepRight,
    orThen,
    pstring,
    satisfy,
    setLabel,
    mapP,
    anyOf,
    keepLeft,
    andThen};

class JsonParser
{

    public function returnObject($obj) {
        return carrying(function($param) use ($obj) {
            return $obj;
        });
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
            $fn = carrying(function ($param) use ($item) {return $item[1];});
            return mapP($fn, $parser);
        }, $map);

        return setLabel(choice($parr), 'escaped char');
    }

    public function jUnicodeChar() {
        $backslash = pchar('\\');
        $uChar = pchar('u');
        $hexdigit = anyOf(['0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
                            'A', 'B', 'C', 'D', 'E', 'F', 'a', 'b', 'c', 'd', 'e', 'f']);

        $convertToChar = carrying(function ($param) {
            [[[$h1,$h2], $h3], $h4] = $param;
            return sprintf('\u%s%s%s%s', $h1, $h2, $h3, $h4);
        });
        $parser = andThen(andThen(andThen(keepRight(keepRight($backslash, $uChar), $hexdigit), $hexdigit), $hexdigit), $hexdigit);
        return mapP($convertToChar, $parser);

    }

    public function jString() {
        $quote = setLabel(pchar('"'), 'quote');
        $jchar = orThen(orThen($this->jUnescapedChar(), $this->jEscapedChar()), $this->jUnicodeChar());

        $parser = keepLeft(keepRight($quote, manyChars($jchar)), $quote);
        $fun = carrying(function($str) { return new JsonString($str);});

        return setLabel(mapP($fun, $parser), "quoted string");
    }


}