<?php

namespace Drupal\charts_google\Settings\Google;

class ChartType implements \JsonSerializable {
  private $type;

  /**
   * @return mixed
   */
  public function getChartType() {
    return $this->type;
  }

  /**
   * @param mixed $type
   */
  public function setChartType($type) {
    $ucType = ucfirst($type);
    $this->type = $ucType . 'Chart';
  }

  /**
   * @return array
   */
  public function jsonSerialize() {
    $vars = get_object_vars($this);

    return $vars;
  }

}
