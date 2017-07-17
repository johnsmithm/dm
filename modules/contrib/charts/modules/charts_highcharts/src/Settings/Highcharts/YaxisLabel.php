<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

class YaxisLabel implements \JsonSerializable {
  private $overflow = 'justify';

  /**
   * @param $overflow
   */
  public function setOverflow($overflow) {
    $this->overflow = $overflow;
  }

  /**
   * @return string
   */
  public function getOverflow() {
    return $this->overflow;
  }

  /**
   * @return array
   */
  public function jsonSerialize() {
    $vars = get_object_vars($this);

    return $vars;
  }

}
