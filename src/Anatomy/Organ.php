<?php

namespace MockChain\Anatomy;

/**
 * @codeCoverageIgnore
 */
class Organ
{
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}
