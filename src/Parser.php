<?php


namespace Wow;


class Parser {
    public $parseFn;
    public $label;

    public function __construct($fn, $label) {
        $this->parseFn = $fn;
        $this->label = $label;
    }

    public function label($label) {
        $this->label = $label;
    }

    public function __get($name) {
        if ($name == 'parseFn')  {
            return $this->parseFn;
        }

        if ($name == 'label')  {
            return $this->label ?? '';
        }
    }
}

