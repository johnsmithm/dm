<?php

namespace Drupal\charts\Services;

use Drupal\Core\Config\ConfigFactory;

class ChartsSettingsService implements ChartsSettingsServiceInterface {

  //private $editableConfigName = 'charts.settings';
  private $configFactory;

  public function __construct(ConfigFactory $config_factory) {
    $this->configFactory = $config_factory;
  }

  public function getChartsSettings() {
    $config = $this->configFactory->getEditable('charts.settings');

    return $config->get('charts_default_settings');
  }
}