<?php

namespace Drupal\charts_c3\Settings\CThree;

class CThree implements \JsonSerializable {
  private $color;
  private $bindto;
  private $data;
  private $axis;
  private $title;

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
  public function getAxis() {
    return $this->axis;
  }

  /**
   * @param mixed $axis
   */
  public function setAxis($axis) {
    $this->axis = $axis;
  }

  /**
   * @return mixed
   */
  public function getData() {
    return $this->data;
  }

  /**
   * @param mixed $data
   */
  public function setData($data) {
    $this->data = $data;
  }

  /**
   * @return string
   */
  public function getBindTo() {
    return $this->bindto;
  }

  /**
   * @param mixed $bindto
   */
  public function setBindTo($bindto) {
    $this->bindto = $bindto;
  }

  /**
   * @return mixed
   */
  public function getColor() {
    return $this->color;
  }

  /**
   * @param mixed $color
   */
  public function setColor($color) {
    $this->color = $color;
  }

  /**
   * @return array
   */
  public function jsonSerialize() {
    $vars = get_object_vars($this);

    return $vars;
  }

}
