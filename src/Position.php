<?php


namespace Wow;


class Position
{
    public $line;
    public $column;
    
    public function __construct(int $line = 0, int $column = 0) {
        $this->line = $line;
        $this->column = $column;
    }
    
    public function incrCol() {
        $this->column += 1;
    }
    
    public function incrLine() {
        $this->line += 1;
        $this->column = 0;
    }
}