<?php

namespace Drupal\charts_c3\Settings\CThree;

class ChartData implements \JsonSerializable {
  private $columns = [];
  private $type;
  private $labels = TRUE;
  private $x = 'x';

  /**
   * @return array
   */
  public function getX() {
    return $this->x;
  }

  /**
   * @param array $x
   */
  public function setX($x) {
    $this->x = $x;
  }

  /**
   * @return array
   */
  public function getColumns() {
    return $this->columns;
  }

  /**
   * @param array $columns
   */
  public function setColumns($columns) {
    $this->columns = $columns;
  }

  /**
   * @return mixed
   */
  public function getType() {
    return $this->type;
  }

  /**
   * @param mixed $type
   */
  public function setType($type) {
    $this->type = $type;
  }

  /**
   * @return mixed
   */
  public function getLabels() {
    return $this->labels;
  }

  /**
   * @param mixed $labels
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
