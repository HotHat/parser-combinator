<?php


namespace Wow\Json;


class JsonObject extends JsonValue
{
    public function __toString() {
        $arr = [];
        foreach ($this->val as $key => $value) {

            $arr[] = sprintf('"%s": %s', $key, $value);
        }

        return 'JsonObject(' . implode(', ', $arr) . ')';
    }

}