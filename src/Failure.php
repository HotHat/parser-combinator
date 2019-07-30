<?php


namespace Wow;


class Failure extends Result
{
    private $reason;

    public function __construct($reason) {
        $this->reason = $reason;
    }

    public function __toString() {
        return sprintf('"%s"', $this->reason);
    }
}
