<?php

namespace MockChainTest;

use MockChain\Sequence;
use PHPUnit\Framework\TestCase;

class SequenceTest extends TestCase
{
    public function test() {
        $sequence = new Sequence();
        $sequence->add(1);
        $sequence->add(2);

        $this->assertEquals($sequence->return(), 1);
        $this->assertEquals($sequence->return(), 2);
        $this->assertEquals($sequence->return(), 2);
    }
}