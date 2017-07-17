<?php

namespace Drupal\charts_api_example\Controller;


use Drupal\charts\Services\ChartsSettingsService;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ChartsApiExample extends ControllerBase implements ContainerInjectionInterface {
  private $chartSettings;

  public function __construct(ChartsSettingsService $chartSettings) {
    $this->chartSettings = $chartSettings->getChartsSettings();
  }

  public function display() {

    $library = $this->chartSettings['library'];
    $options = [];
    $options['type'] = $this->chartSettings['type'];
    $options['title'] = $this->t('Chart title');
    $options['yaxis_title'] = $this->t('Y-Axis');
    $options['yaxis_min'] = '';
    $options['yaxis_max'] = '';
    $options['xaxis_title'] = $this->t('X-Axis');
    //sample data format
    $categories = ["Category 1", "Category 2", "Category 3", "Category 4"];
    $seriesData = [
      ["name" => "Series 1", "color" => "#0d233a", "type" => null, "data" => [250, 350, 400, 200]],
      ["name" => "Series 2", "color" => "#8bbc21", "type" => "column", "data" => [150, 450, 500, 300]],
      ["name" => "Series 3", "color" => "#910000", "type" => "area", "data" => [0, 0, 60, 90]]
    ];

    $element = [
      '#theme' => 'charts_api_example',
      '#library' => $this->t($library),
      '#categories' => $categories,
      '#seriesData' => $seriesData,
      '#options' => $options,
    ];
    return $element;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('charts.settings')
    );
  }
}