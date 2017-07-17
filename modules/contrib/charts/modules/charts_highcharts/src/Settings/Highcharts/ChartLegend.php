<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

class ChartLegend implements \JsonSerializable {
  private $layout = 'vertical';
  private $align = 'right';
  private $verticalAlign = 'top';
  private $x = -40;
  private $y = 80;
  private $floating = TRUE;
  private $borderWidth = 1;
  private $backgroundColor = '#FCFFC5';
  private $shadow = TRUE;

  /**
   * @return string
   */
  public function getLayout() {
    return $this->layout;
  }

  /**
   * @param string $layout
   */
  public function setLayout($layout) {
    $this->layout = $layout;
  }

  /**
   * @return string
   */
  public function getAlign() {
    return $this->align;
  }

  /**
   * @param string $align
   */
  public function setAlign($align) {
    $this->align = $align;
  }

  /**
   * @return string
   */
  public function getVerticalAlign() {
    return $this->verticalAlign;
  }

  /**
   * @param string $verticalAlign
   */
  public function setVerticalAlign($verticalAlign) {
    $this->verticalAlign = $verticalAlign;
  }

  /**
   * @return int
   */
  public function getX() {
    return $this->x;
  }

  /**
   * @param int $x
   */
  public function setX($x) {
    $this->x = $x;
  }

  /**
   * @return int
   */
  public function getY() {
    return $this->y;
  }

  /**
   * @param int $y
   */
  public function setY($y) {
    $this->y = $y;
  }

  /**
   * @return boolean
   */
  public function isFloating() {
    return $this->floating;
  }

  /**
   * @param boolean $floating
   */
  public function setFloating($floating) {
    $this->floating = $floating;
  }

  /**
   * @return int
   */
  public function getBorderWidth() {
    return $this->borderWidth;
  }

  /**
   * @param int $borderWidth
   */
  public function setBorderWidth($borderWidth) {
    $this->borderWidth = $borderWidth;
  }

  /**
   * @return string
   */
  public function getBackgroundColor() {
    return $this->backgroundColor;
  }

  /**
   * @param string $backgroundColor
   */
  public function setBackgroundColor($backgroundColor) {
    $this->backgroundColor = $backgroundColor;
  }

  /**
   * @return boolean
   */
  public function isShadow() {
    return $this->shadow;
  }

  /**
   * @param boolean $shadow
   */
  public function setShadow($shadow) {
    $this->shadow = $shadow;
  }

  /**
   * @return array
   */
  public function jsonSerialize() {
    $vars = get_object_vars($this);

    return $vars;
  }

}
