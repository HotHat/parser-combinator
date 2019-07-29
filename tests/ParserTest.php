<?php declare(strict_types=1);

namespace Wow;


class ParserTest extends \PHPUnit\Framework\TestCase {

    public function testHello() {
        echo 'hello';

        $this->assertEquals(1, 1);
    }

    public function testPChar() {
        $a = pchar('a');

        $r = run($a, 'abc');

        $this->assertEquals($r instanceof Success, true);
    }

}
