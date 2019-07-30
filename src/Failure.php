<?php


namespace Wow;


class Failure extends Result
{
    private $reason;
    private $label;

    public function __construct($label, $reason) {
        $this->reason = $reason;
        $this->label = $label;
    }

    public function __toString() {
        return sprintf("Error parsing %s\n%s", $this->label, $this->reason);
    }
    
    public function __get($name) {
        if ($name == 'label') {
            return $this->label;
        }
        
        if ($name == 'reason') {
            return $this->reason;
        }
        
        assert(false, 'Have not this attribute: ' . $name);
    
    }
}
