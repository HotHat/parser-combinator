<?php


namespace Wow\Json;


class JsonString extends JsonValue
{

    public function __toString()
    {
        return sprintf('JsonString("%s")', $this->val);
    }
}