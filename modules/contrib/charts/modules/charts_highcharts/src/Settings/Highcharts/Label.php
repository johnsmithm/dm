<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

class Label implements \JsonSerializable {
  private $rotation;

  /**
   * @return mixed
   */
  public function getRotation() {
    return $this->rotation;
  }

  /**
   * @param mixed $rotation
   */
  public function setRotation($rotation) {
    $this->rotation = $rotation;
  }

  /**
   * @return array
   */
  public function jsonSerialize() {
    $vars = get_object_vars($this);

    return $vars;
  }

}
