<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

class XaxisTitle extends ChartTitle implements \JsonSerializable {

  private $text;

  /**
   * @return string
   */
  public function getText() {
    return $this->text;
  }

  /**
   * @param string $text
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
