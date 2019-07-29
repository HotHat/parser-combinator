<?php


namespace Wow;


class Failure implements Result
{
    private $reason;

    public function __construct($reason) {
        $this->reason = $reason;
    }

    public function __toString() {
        return sprintf('"%s"', $this->reason);
    }
}

function failure($reason) {
    return new Failure($reason);
}