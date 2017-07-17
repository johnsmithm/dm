<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

class Highcharts implements \JsonSerializable {
  private $chart;
  private $title;
  private $xAxis;
  // private $yAxis;
  private $tooltip;
  private $plotOptions;
  private $legend;
  private $credits;

  /**
   * @return mixed
   */
  public function getChart() {
    return $this->chart;
  }

  /**
   * @param mixed $chart
   */
  public function setChart($chart) {
    $this->chart = $chart;
  }

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
  public function getXAxis() {
    return $this->xAxis;
  }

  /**
   * @param mixed $xAxis
   */
  public function setXAxis($xAxis) {
    $this->xAxis = $xAxis;
  }

  /**
   * @return mixed
   */
  /*public function getYAxis() {
    return $this->yAxis;
  }*/

  /**
   * @param mixed $yAxis
   */
  /* public function setYAxis($yAxis) {
     $this->yAxis = $yAxis;
   }*/

  /**
   * @return mixed
   */
  public function getTooltip() {
    return $this->tooltip;
  }

  /**
   * @param mixed $tooltip
   */
  public function setTooltip($tooltip) {
    $this->tooltip = $tooltip;
  }

  /**
   * @return mixed
   */
  public function getPlotOptions() {
    return $this->plotOptions;
  }

  /**
   * @param mixed $plotOptions
   */
  public function setPlotOptions($plotOptions) {
    $this->plotOptions = $plotOptions;
  }

  /**
   * @return mixed
   */
  public function getLegend() {
    return $this->legend;
  }

  /**
   * @param mixed $legend
   */
  public function setLegend($legend) {
    $this->legend = $legend;
  }

  /**
   * @return mixed
   */
  public function getCredits() {
    return $this->credits;
  }

  /**
   * @param mixed $credits
   */
  public function setCredits($credits) {
    $this->credits = $credits;
  }

  /**
   * @return mixed
   */
  public function getSeries() {
    return $this->series;
  }

  /**
   * @param mixed $series
   */
  public function setSeries($series) {
    $this->series = $series;
  }

  private $series;

  /**
   * @return array
   */
  public function jsonSerialize() {
    $vars = get_object_vars($this);

    return $vars;
  }

}
