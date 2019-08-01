<?php


namespace Wow;


class ParserPosition
{

    private $currentLine;
    private $line;
    private $column;

    public function __construct($currentLine, $line, $column) {
        $this->currentLine = $currentLine;
        $this->line = $line;
        $this->column = $column;
    }

    public function __get($name) {
        if ($name == 'currentLine')  {
            return $this->currentLine;
        }

        if ($name == 'line')  {
            return $this->line;
        }

        if ($name == 'column')  {
            return $this->column;
        }

        assert(false, 'Have not this attribute: ' . $name);
    }
}