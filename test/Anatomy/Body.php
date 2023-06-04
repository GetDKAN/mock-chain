<?php

namespace MockChainTest\Anatomy;

class Body
{
    private array $systems = [];

    public function addSystem(System $system): void
    {
        $this->systems[] = $system;
    }

    public function getSystem(string $name)
    {
        /** @var $system  System*/
        foreach ($this->systems as $system) {
            if ($system->getName() == $name) {
                return $system;
            }
        }
        return null;
    }

    /**
     * @return mixed[]
     */
    public function getSystems(): array
    {
        $systems = [];

        /* @var $system  System */
        foreach ($this->systems as $system) {
            $systems[] = $system->getName();
        }
        return $systems;
    }

    /**
     * @return mixed[]
     */
    public function getOrgans(): array
    {
        $organs = [];
        /* @var $system  System */
        foreach ($this->systems as $system) {
            /* @var $organ Organ */
            foreach ($system->getOrgans() as $organ) {
                $organs[] = $organ->getName();
            }
        }
        return $organs;
    }
}
