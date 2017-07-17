<?php

namespace Drupal\charts_c3\Charts;

use Drupal\charts\Charts\ChartsRenderInterface;
use Drupal\charts\Util\Util;
use Drupal\charts_c3\Settings\CThree\ChartType;
use Drupal\charts_c3\Settings\CThree\CThree;
use Drupal\charts_c3\Settings\CThree\ChartTitle;
use Drupal\charts_c3\Settings\CThree\ChartData;
use Drupal\charts_c3\Settings\CThree\ChartColor;
use Drupal\charts_c3\Settings\CThree\ChartAxis;

class C3ChartsRender implements ChartsRenderInterface {

  public function __construct() {
    Util::checkMissingLibrary('charts_c3', '/vendor/cthree/c3.min.js');
  }

  /**
   * @param $options
   * @param array $categories
   * @param array $seriesData
   * @param $chartId
   * @param array $attachmentDisplayOptions
   *
   * @return CThree
   */
  public function charts_render_charts($options, $categories = [], $seriesData = [], $attachmentDisplayOptions = [], &$variables, $chartId) {
    $noAttachmentDisplays = count($attachmentDisplayOptions) === 0;
    $yAxis = [];
    $types = [];
    //sets secondary axis from the first attachment only
    if (!$noAttachmentDisplays && $attachmentDisplayOptions[0]['inherit_yaxis'] == 0) {
      $yAxis[$seriesData[1]['name']] = 'y2';
    }
    // @todo - make this work for more that one attachment.
    for ($i = 1; $i <= count($attachmentDisplayOptions); $i++) {
      if ($attachmentDisplayOptions[$i - 1]['style']['options']['type'] == 'column')
        $types[$seriesData[$i + 1]['name']] = 'bar';
      else
        $types[$seriesData[$i + 1]['name']] = $attachmentDisplayOptions[$i - 1]['style']['options']['type'];
    }
    $c3Data = [];
    for ($i = 0; $i < count($seriesData); $i++) {
      $c3DataTemp = $seriesData[$i]['data'];
      array_unshift($c3DataTemp, $seriesData[$i]['name']);
      array_push($c3Data, $c3DataTemp);
    }

    $c3Chart = new ChartType();
    $c3Chart->setType($options['type']);
    $c3ChartTitle = new ChartTitle();
    $c3ChartTitle->setText($options['title']);
    $chartAxis = new ChartAxis();
    $c3 = new CThree();
    $bindTo = '#' . $chartId;
    $c3->setBindTo($bindTo);
    $c3->setTitle($c3ChartTitle);
    $chartData = new ChartData();
    if ($noAttachmentDisplays > 0) {
      $chartData->setLabels(FALSE);
    }
    if (!$noAttachmentDisplays && $attachmentDisplayOptions[0]['inherit_yaxis'] == 0) {
      $chartData->axes = $yAxis;
      $showSecAxis['show'] = true;
      $showSecAxis['label'] = $attachmentDisplayOptions[0]['style']['options']['yaxis_title'];
      $chartAxis->y2 = $showSecAxis;
    }
    $chartData->setType($options['type']);
    $c3->setData($chartData);
    if ($options['type'] == 'bar') {
      $chartAxis->setRotated(TRUE);
      array_unshift($categories, 'x');
      array_push($c3Data, $categories);
      $chartData->setColumns($c3Data);
    } else {
      if ($options['type'] == 'column') {
        $chartData->setType('bar');
        $chartAxis->setRotated(FALSE);
        array_unshift($categories, 'x');
        array_push($c3Data, $categories);
        $chartData->setColumns($c3Data);
      } else {
        array_unshift($categories, 'x');
        array_push($c3Data, $categories);
        $chartData->setColumns($c3Data);
      }
    }
    $chartData->types = $types;

    $c3->setAxis($chartAxis);

    $chartColor = new ChartColor();
    $seriesColors = [];
    for ($i = 0; $i < count($seriesData); $i++) {
      $seriesColor = $seriesData[$i]['color'];
      array_push($seriesColors, $seriesColor);
    }
    $chartColor->setPattern($seriesColors);
    $c3->setColor($chartColor);

    $variables['chart_type'] = 'c3';
    $variables['content_attributes']['data-chart'][] = json_encode($c3);
    $variables['attributes']['id'][0] = $chartId;
    $variables['attributes']['class'][] = 'charts-c3';
  }
}
