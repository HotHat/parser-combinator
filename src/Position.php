<?php


namespace Wow;


class Position
{
    public $line;
    public $column;
    
    public function __construct() {
        $this->line = 0;
        $this->column = 0;
    }
    
    public function incrCol() {
        $this->column += 1;
    }
    
    public function incrLine() {
        $this->line += 1;
    }
}