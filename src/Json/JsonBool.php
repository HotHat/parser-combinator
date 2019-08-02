<?php


namespace Wow\Json;


class JsonBool extends JsonValue
{

    public function __toString() {
       if ($this->val === 'true') {
           return sprintf("JsonBool('true')");
       }

       return sprintf("JsonBool('false')");
    }
}