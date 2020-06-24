<?php

namespace MockChain;

class Sequence
{

    private $sequence = [];
    private $counter = 0;

    public function add($return)
    {
        $this->sequence[] = $return;

        return $this;
    }

    public function return()
    {
        $index = $this->counter;
        $lastIndex = count($this->sequence) - 1;
        // Always return the last element when done.
        if ($index > $lastIndex) {
            $index = $lastIndex;
        }

        $return = $this->sequence[$index];
        $this->counter++;
        return $return;
    }
}
