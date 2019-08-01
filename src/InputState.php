<?php
namespace Wow;


class InputState
{
    private $lines;
    private $position;

    public function __construct($lines, Position $position) {
        $this->lines = $lines;
        $this->position = $position;
    }

    public function currentLine() {
        if ($this->position->line < count($this->lines)) {
            return $this->lines[$this->position->line];
        }

        return 'end of file';
    }

    public function nextChar() : Maybe {
        if ($this->position->line >= count($this->lines)) {
            return  new None();
        } else {
            $currentLine = $this->currentLine();

            if ($this->position->column < mb_strlen($currentLine)) {
                $char = mb_substr($currentLine, $this->position->column, 1);
                $this->position->incrCol();

            } else {
                $char = "\n";
                $this->position->incrLine();
            }

            return new Some($char);
        }
    }

    public function backChar() {
        if ($this->position->column > 0) {
            $this->position->decCol();
            return;
        }

        if ($this->position->line == 0) {
            assert(false, 'This is the first line can\'t back to preview line');
        }

        $pre = $this->lines[$this->position->line - 1];

        $col = mb_strlen($pre);
        $this->position->decLine($col);
    }

    public function toParserPosition() {
        $currentLine = $this->currentLine();
        return new ParserPosition($currentLine, $this->position->line, $this->position->column);
    }

    public function readAllChars() {
        $maybe = $this->nextChar();

        if ($maybe instanceof None) {
            return [];
        }

        $char = $maybe->val;
        $result = [$char];

        while ($maybe instanceof Some) {
            $maybe = $this->nextChar();

            if ($maybe instanceof Some) {
                $result[] = $maybe->val;
            }
        }

        return $result;

    }

    public function __get($name) {
        if ($name == 'lines')  {
            return $this->lines;
        }

        if ($name == 'position')  {
            return $this->position;
        }

        assert(false, 'Have not this attribute: ' . $name);
    }

}