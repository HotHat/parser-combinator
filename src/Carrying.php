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
            $this->params = [];
            return ($this->fun)(...$arr);
        } else if (count($this->params) > $this->argNum )  {
            assert(false, 'parameters more than ' . $this->argNum);
        }

        return $this;
    }
}
