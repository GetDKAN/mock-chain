<?php

namespace MockChain;

class Options {

  private $options;
  private $storeId;

  public function __construct() {
    $this->options = [];
    $this->storeId = NULL;
  }

  public function use($storeId) {
    $this->storeId = $storeId;
    return $this;
  }

  public function getUse() {
    return $this->storeId;
  }

  public function add($option, $return) {
    $this->options[$option] = $return;
    return $this;
  }

  public function options() {
    return array_keys($this->options);
  }

  public function return($option) {
    $return = $this->options[$option];

    if ($return instanceof Sequence) {
      $return = $return->return();
    }

    return $return;
  }

}
