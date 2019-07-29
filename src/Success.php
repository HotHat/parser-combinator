<?php
namespace Wow;


class Success implements Result
{
    private $result;
    private $next;

    public function __construct($result, $next)
    {

        $this->result = $result;
        $this->next   = $next;
    }

    public function getAst() {
        return $this->result;
    }

    public function getNext() {
        return $this->next;
    }

    public function __toString()
    {
        return sprintf('Success(%s, "%s")', json_encode($this->result), $this->next);
    }
}

function success($result, $next) {
    return new Success($result, $next);
}