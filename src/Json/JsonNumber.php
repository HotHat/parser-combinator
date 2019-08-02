<?php


namespace Wow\Json;


class JsonNumber extends JsonValue
{
    public function __toString()
    {
        return sprintf("JsonNumber(%s)", $this->val);
    }
}