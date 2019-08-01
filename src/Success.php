<?php
namespace Wow;


class Success extends Result
{
    private $result;
    private $remain;

    public function __construct($result, $remain)
    {

        $this->result = $result;
        $this->remain   = $remain;
    }

    public function __toString()
    {
        if (is_array($this->result)) {
            $val = json_encode($this->result);
        } else {
            $val = strval($this->result);
        }
        return sprintf('Success(%s, "%s")', $val, json_encode($this->remain->readAllchars()));
    }

    public function __get($name) {
        if ($name == 'result')  {
            return $this->result;
        }

        if ($name == 'remain')  {
            return $this->remain;
        }
        
        assert(false, 'Have not this attribute: ' . $name);
    }
}
