<?php


namespace Wow\Json;


class JsonString extends JsonValue
{

    public function __toString()
    {
        return (string)$this->val;
    }
}