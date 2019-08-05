<?php


namespace Wow\Json;


class JsonArray extends JsonValue
{

    public function __toString() {
        $arr = [];
        foreach ($this->val as $item) {

            $arr[] = sprintf('%s', $item);
        }

        return 'JsonArray[' . implode(', ', $arr) . ']';
    }

}