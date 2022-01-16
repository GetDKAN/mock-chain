<?php

namespace MockChain;

/**
 * Define return values for options passed to a single mock in a chain.
 *
 * @package MockChain
 *
 * @todo Options for complex things, like objects.
 */
class Options
{

    private $options;
    private $storeId;

    /**
     * The position in the inputs array of the relevant data for this option.
     */
    private $index = null;

    public function __construct()
    {
        $this->options = [];
        $this->storeId = null;
    }

    public function use($storeId)
    {
        $this->storeId = $storeId;
        return $this;
    }

    public function getUse()
    {
        return $this->storeId;
    }

    public function index($index)
    {
        $this->index = $index;
        return $this;
    }

    public function getIndex()
    {
        return $this->index;
    }

    public function add($option, $return)
    {
        $option = is_array($option) ? json_encode($option) : $option;
        $this->options[$option] = $return;
        return $this;
    }

    public function options()
    {
        return array_keys($this->options);
    }

    public function return($option)
    {
        if (!isset($this->options[$option])) {
            return;
        }

        $return = $this->options[$option];

        if ($return instanceof Sequence) {
            $return = $return->return();
        }

        return $return;
    }
}
