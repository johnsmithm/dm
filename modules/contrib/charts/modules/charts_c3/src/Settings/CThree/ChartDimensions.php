<?php

namespace Drupal\charts_c3\Settings\CThree;

class ChartDimensions implements \JsonSerializable {
  private $ratio;

  /**
   * @return mixed
   */
  public function getRatio() {
    return $this->ratio;
  }

  /**
   * @param mixed $ratio
   */
  public function setRatio($ratio) {
    $this->ratio = $ratio;
  }

  /**
   * @return array
   */
  public function jsonSerialize() {
    $vars = get_object_vars($this);

    return $vars;
  }

}
