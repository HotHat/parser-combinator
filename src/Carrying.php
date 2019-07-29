<?php

namespace Wow;


class Carrying
{
    private $fun;
    private $args;
    private $params = [];

    public function __construct(\Closure $fun)
    {
        $this->fun = $fun;
        $rf = new \ReflectionFunction($fun);
        $this->args = $rf->getParameters();
    }

    public function invoke(...$params) {
        $this->params = array_merge($this->params, $params);
        if (count($this->params) == count($this->args) ) {
            return ($this->fun)(...$this->params);
        } else if (count($this->params) > count($this->args) )  {
            assert(false, 'parameters more than ' . count($this->args));
        }

        return $this;
    }
}

function carrying($fun) {
    return new Carrying($fun);
}