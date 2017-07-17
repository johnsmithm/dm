<?php

namespace Drupal\charts\Services;

/**
 * Interface ChartServiceInterface.
 *
 * @package Drupal\charts\Services
 */

interface ChartServiceInterface {
  /**
   * @return mixed
   */

  public function getLibrarySelected();

  /**
   * @param $librarySelected
   */
  public function setLibrarySelected($librarySelected);

}
