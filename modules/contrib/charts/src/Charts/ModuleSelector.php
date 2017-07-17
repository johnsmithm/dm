<?php

/**
 * @ file
 *
 *
 */

namespace Drupal\charts\Charts;

class ModuleSelector {

  private $moduleName;
  private $assetLocation = '/vendor/';
  private $assetName;
  private $categories;
  private $seriesData;
  private $options;
  private $attachmentDisplayOptions;
  private $chartId;

  public function __construct($moduleName, $categories, $seriesData, $options, $attachmentDisplayOptions, &$variables, $chartId) {
    $this->moduleName = $moduleName;
    $this->categories = $categories;
    $this->seriesData = $seriesData;
    $this->options = $options;
    $this->attachmentDisplayOptions = $attachmentDisplayOptions;
    $this->chartId = $chartId;
    $this->moduleExists($moduleName, $variables);
  }

  private function moduleExists($moduleName, &$variables) {
    $moduleExist = \Drupal::moduleHandler()->moduleExists($moduleName);
    if ('charts_' . $moduleExist) {
      $moduleChartsRenderer = 'Drupal\charts_' . $moduleName . '\Charts\\' . ucfirst($moduleName) . 'ChartsRender';
      $chartingModule = new $moduleChartsRenderer();
      $chartingModule->charts_render_charts($this->options, $this->categories, $this->seriesData, $this->attachmentDisplayOptions, $variables, $this->chartId);
    }
  }
}
