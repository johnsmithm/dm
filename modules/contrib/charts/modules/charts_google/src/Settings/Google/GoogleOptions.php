<?php

namespace Drupal\charts_google\Settings\Google;

class GoogleOptions implements \JsonSerializable {
  private $title;
  private $chartArea;
  private $hAxis;
  private $vAxis;
  private $colors;

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
   * @return mixed
   */
  public function getChartArea() {
    return $this->chartArea;
  }

  /**
   * @param mixed $chartArea
   */
  public function setChartArea($chartArea) {
    $this->chartArea = $chartArea;
  }

  /**
   * @return mixed
   */
  public function getHAxis() {
    return $this->hAxis;
  }

  /**
   * @param mixed $hAxis
   */
  public function setHAxis($hAxis) {
    $this->hAxis = $hAxis;
  }

  /**
   * @return mixed
   */
  public function getVAxis() {
    return $this->vAxis;
  }

  /**
   * @param mixed $vAxis
   */
  public function setVAxis($vAxis) {
    $this->vAxis = $vAxis;
  }

  /**
   * @return mixed
   */
  public function getColors() {
    return $this->colors;
  }

  /**
   * @param mixed $colors
   */
  public function setColors($colors) {
    $this->colors = $colors;
  }

  /**
   * @return array
   */
  public function jsonSerialize() {
    $vars = get_object_vars($this);

    return $vars;
  }

}
