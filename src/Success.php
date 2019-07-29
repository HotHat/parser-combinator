<?php
namespace Wow;


class Success implements Result
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
        return sprintf('Success(%s, "%s")', json_encode($this->result), $this->remain);
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
