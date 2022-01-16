<?php

namespace MockChainTest;

use MockChain\Chain;
use MockChainTest\Anatomy\Body;
use MockChainTest\Anatomy\System;
use MockChainTest\Anatomy\Organ;
use MockChain\Options;
use MockChain\Sequence;
use PHPUnit\Framework\TestCase;

class ChainTest extends TestCase
{

    public function testDocs()
    {
        $organs = (new Options())
            ->add(json_encode(["lung", 0]), "yep, the left lung")
            ->add(json_encode(["lung", 1]), "yep, the right lung");

        $mock = (new Chain($this))
            ->add(System::class, "getOrganByNameAndIndex", $organs)
            ->add(Organ::class, "getName", "heart")
            ->getMock();

        $this->assertEquals("yep, the left lung", $mock->getOrganByNameAndIndex("lung", 0));
        $this->assertEquals("yep, the right lung", $mock->getOrganByNameAndIndex("lung", 1));
    }

    public function test()
    {
        $organNames = (new Sequence())->add('mouth')->add('stomach');

        $organ = (new Chain($this))
            ->add(Organ::class, "getName", $organNames)
            ->getMock();

        $system = (new Chain($this))
            ->add(System::class, 'getName', "digestive")
            ->addd('getOrgans', [$organ, $organ])
            ->addd('getOrgan', new Organ('mouth'))
            ->getMock();

        $body = new Body();
        $body->addSystem($system);

        $this->assertEquals(json_encode($body->getOrgans()), json_encode(['mouth', 'stomach']));
        $this->assertEquals(json_encode($body->getSystems()), json_encode(['digestive']));
        $this->assertEquals($body->getSystem('digestive')->getOrgan('mouth')->getName(), 'mouth');
    }

    public function test2()
    {
        $organNames = (new Sequence())->add('mouth')->add('stomach');

        $organ = (new Chain($this))
            ->add(Organ::class, "getName", $organNames)
            ->getMock();

        $system = (new Chain($this))
            ->add(System::class, 'getName', "digestive")
            ->add(System::class, 'getOrgans', [$organ, $organ])
            ->add(System::class, 'getOrgan', Organ::class)
            ->add(Organ::class, 'getName', 'mouth')
            ->getMock();

        $body = new Body();
        $body->addSystem($system);

        $this->assertEquals(json_encode($body->getOrgans()), json_encode(['mouth', 'stomach']));
        $this->assertEquals(json_encode($body->getSystems()), json_encode(['digestive']));
        $this->assertEquals('mouth', $body->getSystem('digestive')->getOrgan('mouth')->getName());
    }


    public function test3()
    {
        $organs = (new Options())
            ->add('mouth', Organ::class)
            ->add('stomach', Organ::class);

        $organNames = (new Options())
            ->add('mouth', 'mouth')
            ->add('stomach', 'stomach')
            ->use('organ');

        $chain = (new Chain($this))
            ->add(System::class, 'getName', "digestive")
            ->add(System::class, 'getOrgan', $organs, 'organ')
            ->add(Organ::class, "getName", $organNames);

        $system = $chain->getMock();

        $body = new Body();
        $body->addSystem($system);

        $this->assertEquals($body->getSystem('digestive')->getOrgan('mouth')->getName(), 'mouth');
        $this->assertEquals($body->getSystem('digestive')->getOrgan('stomach')->getName(), 'stomach');
        $this->assertEquals(json_encode(['stomach']), json_encode($chain->getStoredInput('organ')));
    }

    public function test4()
    {
        $this->expectExceptionMessage("blah");

        $system = (new Chain($this))
            ->add(System::class, 'getName', new \Exception("blah"))
            ->getMock();

        $body = new Body();
        $body->addSystem($system);

        $body->getSystem("blah");
    }

    public function testNonExistentMethod()
    {
        $this->expectExceptionMessage('Trying to set mock method "blah" with onlyMethods');
        (new Chain($this))
          ->add(Organ::class, 'blah', null)
          ->getMock();
    }

    public function testUsingAdddIncorrectly()
    {
        $this->expectExceptionMessage("You should use the add method before using addd.");
        (new Chain($this))->addd("hello");
    }

    public function testNonExistentOption()
    {
        $this->expectExceptionMessage('Option digestive does not exist');
        $options = new Options();
        $mock = (new Chain($this))
            ->add(Body::class, 'getSystem', $options)
            ->getMock();
        $mock->getSystem("digestive");
    }
}
