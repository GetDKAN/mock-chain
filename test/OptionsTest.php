<?php

namespace MockChainTest;

use MockChain\Options;
use MockChain\Sequence;
use PHPUnit\Framework\TestCase;

class OptionsTest extends TestCase
{
    public function test()
    {
        $options = new Options();
        $options->add("hello", "goodbye");
        $options->add("hola", "chao");
        $options->add("multi", (new Sequence())->add('adieu')->add('shalom'));

        $this->assertEquals(json_encode($options->options()), json_encode(["hello", "hola", "multi"]));
        $this->assertEquals($options->return("hello"), "goodbye");
        $this->assertEquals($options->return("hola"), "chao");
        $this->assertEquals($options->return("multi"), "adieu");
        $this->assertEquals($options->return("multi"), "shalom");
        $this->assertEquals($options->return("multi"), "shalom");
        $this->assertNull($options->return('not-an-option'));
        $options->index(2);
        $this->assertEquals(2, $options->getIndex());
    }
}
