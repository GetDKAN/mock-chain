<?php

namespace MockChainTest;

use MockChain\Chain;
use MockChain\ReturnNull;
use MockChain\Sequence;
use MockChainTest\Anatomy\Organ;
use PHPUnit\Framework\TestCase;

class SequenceTest extends TestCase
{
    public function test()
    {
        $sequence = new Sequence();
        $sequence->add(null);
        $sequence->add(1);
        $sequence->add(2);

        $this->assertTrue($sequence->return() instanceof ReturnNull);
        $this->assertEquals($sequence->return(), 1);
        $this->assertEquals($sequence->return(), 2);
        $this->assertEquals($sequence->return(), 2);
    }

    public function testSequenceThroughChain()
    {
        $sequence = (new Sequence())
            ->add(null)
            ->add("hello");

        $mock = (new Chain($this))
            ->add(Organ::class, 'getName', $sequence)
            ->getMock();

        $this->assertNull($mock->getName());
        $this->assertEquals("hello", $mock->getName());
    }
}
