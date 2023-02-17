<?php

namespace MockChainTest\Anatomy;

class System
{
    private string $name;
    private array $organs = [];

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

    public function getOrganByNameAndIndex($name, $index)
    {
        $matchingOrgans = [];

        foreach ($this->organs as $organ) {
            if ($organ->getName() == $name) {
                $matchingOrgans[] = $organ;
            }
        }

        if (isset($matchingOrgans[$index])) {
            return $matchingOrgans[$index];
        }

        throw new \Exception("Couldn't find organ.");
    }
}
