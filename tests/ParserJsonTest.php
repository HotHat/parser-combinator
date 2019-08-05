<?php
namespace Wow\Json;
use SebastianBergmann\CodeCoverage\Report\PHP;
use Wow\Failure;
use function Wow\run;
use Wow\Success;

class ParserJsonTest extends \PHPUnit\Framework\TestCase
{
    private $parser;
    protected function setUp()
    {
        parent::setUp();

        $this->parser = new JsonParser();
    }

    public function testJNull() {

        $pnull = $this->parser->jNull();

        echo '----------------------- 1 -----------------------', PHP_EOL;
        $r = run($pnull, 'nuly');
        echo $r, PHP_EOL;
        $this->assertEquals(true, $r instanceof Failure);

        echo '----------------------- 2 -----------------------', PHP_EOL;
        // $pnull = $this->parser->jNull();
        $r1 = run($pnull, 'null');
        echo $r1, PHP_EOL;
        $this->assertEquals(true, $r1 instanceof Success);

        echo '----------------------- 3 -----------------------', PHP_EOL;
        // $pnull = $this->parser->jNull();
        $r2 = run($pnull, 'nuyl');
        echo $r2, PHP_EOL;
        $this->assertEquals(true, $r2 instanceof Failure);

    }

    public function testeJBool() {
        $pbool = $this->parser->jBool();
        $r = run($pbool, 'true');
        echo $r, PHP_EOL;
        $this->assertEquals(true, $r instanceof Success);

        // $pbool = $this->parser->jBool();
        $r = run($pbool, 'false');
        echo $r, PHP_EOL;
        $this->assertEquals(true, $r instanceof Success);

        // $pbool = $this->parser->jBool();
        $r = run($pbool, 'falsy');
        echo $r, PHP_EOL;
        $this->assertEquals(true, $r instanceof Failure);

        // $pbool = $this->parser->jBool();
        $r = run($pbool, 'falsy');
        echo $r, PHP_EOL;
        $this->assertEquals(true, $r instanceof Failure);


        // $r = run($pbool, 'truX');
        // echo $r, PHP_EOL;
    }

    public function testUnescapedChar() {
        $r = run($this->parser->jUnescapedChar(), 'a');
        echo $r, PHP_EOL;
        $this->assertEquals(true, $r instanceof Success);

        $r = run($this->parser->jUnescapedChar(), '\\');
        echo $r, PHP_EOL;
        $this->assertEquals(false, $r instanceof Success);
    }

    public function testEscapedChar() {
        $r = run($this->parser->jEscapedChar(), '\\\\');
        echo $r, PHP_EOL;
        $this->assertEquals(true, $r instanceof Success);

        $r = run($this->parser->jEscapedChar(), '\t');
        echo $r, PHP_EOL;
        $this->assertEquals(true, $r instanceof Success);

        $r = run($this->parser->jEscapedChar(), '\n');
        echo $r, PHP_EOL;
        $this->assertEquals(true, $r instanceof Success);

        $r = run($this->parser->jEscapedChar(), 'a');
        echo $r, PHP_EOL;
        $this->assertEquals(false, $r instanceof Success);
    }

    public function testUnicodeChar() {
        $r = run($this->parser->jUnicodeChar(), '\\u263A');
        echo $r, PHP_EOL;
        $this->assertEquals(true, $r instanceof Success);
    }

    public function testString() {
        $r = run($this->parser->jString(), '""');
        echo $r, PHP_EOL;
        $this->assertEquals(true, $r instanceof Success);

        $r = run($this->parser->jString(), '"a"');
        echo $r, PHP_EOL;
        $this->assertEquals(true, $r instanceof Success);
        //
        $r = run($this->parser->jString(), '"ab"');
        echo $r, PHP_EOL;
        $this->assertEquals(true, $r instanceof Success);

        $r = run($this->parser->jString(), '"ab\\tde"');
        echo $r, PHP_EOL;
        // $this->assertEquals(true, $r instanceof Success);
        //
        $r = run($this->parser->jString(), '"\u304a\u306f\u3088\u3046\u3054\u3056\u3044\u307e\u3059"');
        echo $r, PHP_EOL;
        $this->assertEquals(true, $r instanceof Success);

    }

    public function testNumber() {
        $r = run($this->parser->jNumber(), '123');
        echo $r, PHP_EOL;
        $this->assertEquals(true, $r instanceof Success);

        $r = run($this->parser->jNumber(), '-123');
        echo $r, PHP_EOL;
        $this->assertEquals(true, $r instanceof Success);

        $r = run($this->parser->jNumber(), '123.4');
        echo $r, PHP_EOL;
        $this->assertEquals(true, $r instanceof Success);

        $r = run($this->parser->jNumber_(), '-123.');
        echo $r, PHP_EOL;
        $this->assertEquals(false, $r instanceof Success);

        $r = run($this->parser->jNumber_(), '00.1');
        echo $r, PHP_EOL;
        $this->assertEquals(false, $r instanceof Success);

        $r = run($this->parser->jNumber_(), '0.1');
        echo $r, PHP_EOL;
        $this->assertEquals(true, $r instanceof Success);

        $r = run($this->parser->jNumber(), '123.4e5');
        echo $r, PHP_EOL;
        $this->assertEquals(true, $r instanceof Success);

        $r = run($this->parser->jNumber(), '123.4e-5');
        echo $r, PHP_EOL;
        $this->assertEquals(true, $r instanceof Success);



    }


    public function testArray() {
        ini_set('xdebug.max_nesting_level', 1800);
        $r = run($this->parser->jArray(), '[1, 2, "String", true, false, null]');
        echo $r;
    }

    public function testArray2()
    {
        ini_set('xdebug.max_nesting_level', 1800);
        $r = run($this->parser->jArray(), '[12, [12]]');
        echo $r;

    }

    public function testArray3()
    {
        ini_set('xdebug.max_nesting_level', 1800);
        $r = run($this->parser->jArray(), '[12,{"A": 123}]');
        echo $r;

    }

    public function testObject() {
        $input = <<< EOF
{ "name" : "Scott", "isMale" : true, "bday" : {"year":2001, "month":12, "day":25 }, "favouriteColors" : ["blue", "green"] }
EOF;


        $r = run($this->parser->jObject(), $input);
        echo $r;
    }

}