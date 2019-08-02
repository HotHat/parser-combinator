<?php


namespace Wow\Json;


class JsonNull extends JsonValue
{
    public function __construct() {
        parent::__construct('null');
    }

    public function __toString() {
        return 'JsonNull';
    }

}