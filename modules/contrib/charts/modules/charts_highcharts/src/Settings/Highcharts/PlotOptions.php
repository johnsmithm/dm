<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

class PlotOptions implements \JsonSerializable {
  private $bar;

  /**
   * @return mixed
   */
  public function getBar() {
    return $this->bar;
  }

  /**
   * @param mixed $bar
   */
  public function setBar($bar) {
    $this->bar = $bar;
  }

  /**
   * @return array
   */
  public function jsonSerialize() {
    $vars = get_object_vars($this);

    return $vars;
  }

}
