<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

class DataLabels implements \JsonSerializable {
  private $dataLabels;

  /**
   * @return mixed
   */
  public function getDataLabels() {
    return $this->dataLabels;
  }

  /**
   * @param mixed $dataLabels
   */
  public function setDataLabels($dataLabels) {
    $this->dataLabels = $dataLabels;
  }

  /**
   * @return array
   */
  public function jsonSerialize() {
    $vars = get_object_vars($this);

    return $vars;
  }

}
