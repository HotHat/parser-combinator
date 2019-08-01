<?php


namespace Wow;


class Failure extends Result
{
    private $reason;
    private $label;
    private $parserPosition;

    public function __construct(string $label, string $reason, ParserPosition $parserPosition) {
        $this->reason = $reason;
        $this->label = $label;
        $this->parserPosition= $parserPosition;
    }

    public function __toString() {
        $colPos = $this->parserPosition->column;
        $linePos = $this->parserPosition->line;
        $errorLine   = $this->parserPosition->currentLine;
        $sub = mb_substr($errorLine, 0, $colPos);

        $failure = sprintf("%s^%s", str_repeat(' ', strlen($sub) - 1), $this->reason);
        return sprintf("Line: %d Col:%d Error parsing %s\n%s\n%s", $linePos,
            $colPos, $this->label, $errorLine, $failure);
    }
    
    public function __get($name) {
        if ($name == 'label') {
            return $this->label;
        }
        
        if ($name == 'reason') {
            return $this->reason;
        }

        if ($name == 'parserPosition') {
            return $this->parserPosition;
        }

        assert(false, 'Have not this attribute: ' . $name);
    
    }
}
