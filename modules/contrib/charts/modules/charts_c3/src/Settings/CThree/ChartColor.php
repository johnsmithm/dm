<?php

namespace Drupal\charts_c3\Settings\CThree;

class ChartColor implements \JsonSerializable {
  private $pattern = [];

  /**
   * @return mixed
   */
  public function getPattern() {
    return $this->pattern;
  }

  /**
   * @param mixed $pattern
   */
  public function setPattern($pattern) {
    $this->pattern = $pattern;
  }

  /**
   * @return array
   */
  public function jsonSerialize() {
    $vars = get_object_vars($this);

    return $vars;
  }

}
