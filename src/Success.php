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
        return sprintf('Success(%s, "%s")', $val, $this->remain);
    }

    public function __get($name) {
        if ($name == 'RESULT')  {
            return $this->result;
        }

        if ($name == 'REMAIN')  {
            return $this->remain;
        }
    }
}
