<?php

namespace Drupal\charts\Services;

/**
 * Service class for getting and setting the currently selected library's state.
 *
 * Class ChartService
 *
 * @package Drupal\charts\Services
 */

class ChartService implements ChartServiceInterface {

  private $librarySelected;

  /**
   * Gets the currently selected Library.
   *
   * @return string $librarySelected
   */
  public function getLibrarySelected() {
    return $this->librarySelected;
  }

  /**
   * Sets the previously set Library with the newly selected library value.
   *
   * @param string $librarySelected
   */
  public function setLibrarySelected($librarySelected) {
    $this->librarySelected = $librarySelected;
  }

}
