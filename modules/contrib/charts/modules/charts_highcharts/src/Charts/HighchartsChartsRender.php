<?php

namespace Drupal\charts_highcharts\Charts;

use Drupal\charts\Charts\ChartsRenderInterface;
use Drupal\charts\Util\Util;
use Drupal\charts_highcharts\Settings\Highcharts\ChartType;
use Drupal\charts_highcharts\Settings\Highcharts\ChartTitle;
use Drupal\charts_highcharts\Settings\Highcharts\Xaxis;
use Drupal\charts_highcharts\Settings\Highcharts\XaxisTitle;
use Drupal\charts_highcharts\Settings\Highcharts\ChartLabel;
use Drupal\charts_highcharts\Settings\Highcharts\YaxisLabel;
use Drupal\charts_highcharts\Settings\Highcharts\Yaxis;
use Drupal\charts_highcharts\Settings\Highcharts\YaxisTitle;
use Drupal\charts_highcharts\Settings\Highcharts\DataLabelStatus;
use Drupal\charts_highcharts\Settings\Highcharts\DataLabels;
use Drupal\charts_highcharts\Settings\Highcharts\PlotOptions;
use Drupal\charts_highcharts\Settings\Highcharts\Tooltip;
use Drupal\charts_highcharts\Settings\Highcharts\ChartCredits;
use Drupal\charts_highcharts\Settings\Highcharts\ChartLegend;
use Drupal\charts_highcharts\Settings\Highcharts\Highcharts;

class HighchartsChartsRender implements ChartsRenderInterface {

  public function __construct() {
    Util::checkMissingLibrary('charts_highcharts', '/vendor/highcharts/highcharts.js');
  }

  /**
   * Creates a JSON Object formatted for Highcharts to use
   *
   * @param $options
   * @param array $categories
   * @param array $seriesData
   *
   * @param array $attachmentDisplayOptions
   *
   * @return Highcharts object to be used by highcharts javascripts visualization framework
   */
  public function charts_render_charts($options, $categories = [], $seriesData = [], $attachmentDisplayOptions = [], &$variables, $chartId) {

    $chart = new ChartType();
    $chart->setType($options['type']);
    $chartTitle = new ChartTitle();
    $chartTitle->setText($options['title']);
    $chartXaxis = new Xaxis();
    $chartLabels = new ChartLabel();
    $chartLabels->setRotation($options['xaxis_labels_rotation']);
    $chartXaxis->setCategories($categories);
    $xAxisTitle = new XaxisTitle();
    $xAxisTitle->setText($options['xaxis_title']);
    $chartXaxis->setTitle($xAxisTitle);
    $chartXaxis->setLabels($chartLabels);
    $yaxisLabels = new YaxisLabel();
    $chartYaxis = new Yaxis();
    $yAxes = [];
    $yAxisTitle = new YaxisTitle();
    $yAxisTitle->setText($options['yaxis_title']);
    if (!empty($options['yaxis_min'])) {
      $chartYaxis->min = $options['yaxis_min'];
    }
    if (!empty($options['yaxis_max'])) {
      $chartYaxis->max = $options['yaxis_max'];
    }

    $chartYaxis->setLabels($yaxisLabels);
    $chartYaxis->setTitle($yAxisTitle);
    array_push($yAxes, $chartYaxis);
    // Chart libraries tend to supports only one secondary axis.
    if ($attachmentDisplayOptions[0]['inherit_yaxis'] == 0) {
      $chartYaxisSecondary = new Yaxis();
      $yAxisTitleSecondary = new YaxisTitle();
      $yAxisTitleSecondary->setText($attachmentDisplayOptions[0]['style']['options']['yaxis_title']);
      $chartYaxisSecondary->setTitle($yAxisTitleSecondary);
      $chartYaxisSecondary->setLabels($yaxisLabels);
      $chartYaxisSecondary->opposite = 'true';

      if (!empty($attachmentDisplayOptions[0]['style']['options']['yaxis_min'])) {
        $chartYaxisSecondary->min = $attachmentDisplayOptions[0]['style']['options']['yaxis_min'];
      }
      if (!empty($attachmentDisplayOptions[0]['style']['options']['yaxis_max'])) {
        $chartYaxisSecondary->max = $attachmentDisplayOptions[0]['style']['options']['yaxis_max'];
      }
      array_push($yAxes, $chartYaxisSecondary);
    }
    $dataLabelStatus = new DataLabelStatus();
    $dataLabels = new DataLabels();
    $dataLabels->setDataLabels($dataLabelStatus);
    $barPlotOptns = new PlotOptions();
    $barPlotOptns->setBar($dataLabels);
    $chartTooltip = new Tooltip();
    $chartCredits = new ChartCredits();
    $chartLegend = new ChartLegend();

    $highchart = new Highcharts();
    $highchart->setChart($chart);
    $highchart->setTitle($chartTitle);
    $highchart->setXAxis($chartXaxis);
    //$highchart->setYAxis($chartYaxis);
    $highchart->yAxis = $yAxes;
    $highchart->setTooltip($chartTooltip);
    $highchart->setPlotOptions($barPlotOptns);
    $highchart->setCredits($chartCredits);
    $highchart->setLegend($chartLegend);
    $highchart->setSeries($seriesData);

    $variables['chart_type'] = 'highcharts';
    $variables['content_attributes']['data-chart'][] = json_encode($highchart);
    $variables['attributes']['id'][0] = $chartId;
    $variables['attributes']['class'][] = 'charts-highchart';
  }
}
