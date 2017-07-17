<?php

namespace Drupal\charts_c3\Settings\CThree;

class ChartTitle implements \JsonSerializable {
  private $text;

  /**
   * @return mixed
   */
  public function getText() {
    return $this->text;
  }

  /**
   * @param mixed $text
   */
  public function setText($text) {
    $this->text = $text;
  }

  /**
   * @return array
   */
  public function jsonSerialize() {
    $vars = get_object_vars($this);

    return $vars;
  }

}
