<?php


namespace Wow;


class Parser {
    public $fn;
    public $label;

    public function __construct($fn) {
        $this->fn = $fn;
    }

    public function label($label) {
        $this->label = $label;
    }
}

function parser($fn) {
    return new Parser($fn);
}