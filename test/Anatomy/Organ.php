<?php

namespace MockChainTest\Anatomy;

class Organ
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function shoutName(): string
    {
        return strtoupper($this->name);
    }
}
