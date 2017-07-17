<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

class Yaxis implements \JsonSerializable {
  private $title;
  private $labels = '';

  /**
   * @return mixed
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * @param mixed $title
   */
  public function setTitle($title) {
    $this->title = $title;
  }

  /**
   * @return string
   */
  public function getLabels() {
    return $this->labels;
  }

  /**
   * @param string $labels
   */
  public function setLabels($labels) {
    $this->labels = $labels;
  }

  /**
   * @return array
   */
  public function jsonSerialize() {
    $vars = get_object_vars($this);

    return $vars;
  }

}
