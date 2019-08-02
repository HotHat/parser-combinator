<?php


namespace Wow\Json;


class JsonValue
{

    protected $val;
    public function __construct(bool $val) {
        $this->val = $val;
    }

    public function __get($name) {
        if ($name == 'val') {
            return $this->val;
        }
        assert(false, 'Some just get value by VAL ');
    }

}