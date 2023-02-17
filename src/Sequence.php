<?php

namespace MockChain;

/**
 * Sequence of return values to respond to multiple calls to the same method.
 *
 * @package MockChain
 */
class Sequence
{

    private $sequence = [];
    private int $counter = 0;

    public function add($return)
    {
        if (!isset($return)) {
            $this->sequence[] = new ReturnNull();
        } else {
            $this->sequence[] = $return;
        }

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
