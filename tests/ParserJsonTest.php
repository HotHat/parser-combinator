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
        $pnull = $this->parser->jNull();
        $r1 = run($pnull, 'null');
        echo $r1, PHP_EOL;
        $this->assertEquals(true, $r1 instanceof Success);

        echo '----------------------- 3 -----------------------', PHP_EOL;
        $pnull = $this->parser->jNull();
        $r2 = run($pnull, 'nuyl');
        echo $r2, PHP_EOL;
        $this->assertEquals(true, $r2 instanceof Failure);

    }

    public function testeJBool() {
        $pbool = $this->parser->jBool();
        $r = run($pbool, 'true');
        echo $r, PHP_EOL;
        $this->assertEquals(true, $r instanceof Success);

        $pbool = $this->parser->jBool();
        $r = run($pbool, 'false');
        echo $r, PHP_EOL;
        $this->assertEquals(true, $r instanceof Success);

        $pbool = $this->parser->jBool();
        $r = run($pbool, 'falsy');
        echo $r, PHP_EOL;
        $this->assertEquals(true, $r instanceof Failure);

        $pbool = $this->parser->jBool();
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

        $r = run($this->parser->jUnicodeChar(), '"a"');
        echo $r, PHP_EOL;
        $this->assertEquals(true, $r instanceof Success);

        $r = run($this->parser->jUnicodeChar(), '"ab"');
        echo $r, PHP_EOL;
        $this->assertEquals(true, $r instanceof Success);

        $r = run($this->parser->jUnicodeChar(), '"ab\\tde"');
        echo $r, PHP_EOL;
        $this->assertEquals(true, $r instanceof Success);

        $r = run($this->parser->jUnicodeChar(), '"ab\\u263Ade"');
        echo $r, PHP_EOL;
        $this->assertEquals(true, $r instanceof Success);

    }

}