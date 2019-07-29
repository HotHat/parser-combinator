<?php


namespace Wow;


class Parser {
    public $fun;
    public $label;

    public function __construct($fn) {
        $this->fun = $fn;
    }

    public function label($label) {
        $this->label = $label;
    }

    public function __get($name) {
        if ($name == 'FUN')  {
            return $this->fun;
        }

        if ($name == 'LABEL')  {
            return $this->label;
        }
    }
}

