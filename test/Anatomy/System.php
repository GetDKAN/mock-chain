<?php

namespace MockChainTest\Anatomy;

class System
{
    private $name;
    private $organs = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function addOrgan(Organ $organ)
    {
        $this->organs[] = $organ;
    }

    public function getOrgan(string $name)
    {
        /** @var $organ  Organ*/
        foreach ($this->organs as $organ) {
            if ($organ->getName() == $name) {
                return $organ;
            }
        }
        return null;
    }

    public function getOrgans()
    {
        return $this->organs;
    }
}
