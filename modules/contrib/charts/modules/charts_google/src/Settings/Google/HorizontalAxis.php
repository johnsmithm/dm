<?php

namespace Drupal\charts_google\Settings\Google;

class HorizontalAxis {
  private $title;
  private $minValue = 0;

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
   * @return int
   */
  public function getMinValue() {
    return $this->minValue;
  }

  /**
   * @param int $minValue
   */
  public function setMinValue($minValue) {
    $this->minValue = $minValue;
  }

}
