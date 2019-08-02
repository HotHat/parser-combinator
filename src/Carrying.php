<?php

namespace Wow;

use ReflectionFunction;
use Closure;

class Carrying
{
    private $fun;
    private $argNum;
    private $params = [];

    public function __construct(Closure $fun)
    {
        $this->fun = $fun;
        $rf = new ReflectionFunction($fun);
        $this->argNum = $rf->getNumberOfParameters();
    }

    public function invoke(...$params) {
        $this->params = array_merge($this->params, $params);
        if (count($this->params) == $this->argNum ) {
            $arr = $this->params;
            $result =($this->fun)(...$arr);
            $this->clear();
            return $result;
        } else if (count($this->params) > $this->argNum )  {
            assert(false, 'parameters more than ' . $this->argNum);
        }

        return $this;
    }

    public function clear() {
        $this->params = [];
    }
}
