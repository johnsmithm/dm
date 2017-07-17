<?php

namespace Drupal\charts_google\Settings\Google;

class ChartArea implements \JsonSerializable {
  private $width;

  /**
   * @return mixed
   */
  public function getWidth() {
    return $this->width;
  }

  /**
   * @param mixed $width
   */
  public function setWidth($width) {
    $this->width = $width;
  }

  /**
   * @return array
   */
  public function jsonSerialize() {
    $vars = get_object_vars($this);

    return $vars;
  }

}
