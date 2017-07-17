<?php

namespace Drupal\charts\Util;

class Util {

  /**
   * @param $view
   * @param $labelValues
   * @param $labelField
   * @param $color
   * @return array
   */
  public static function viewsData($view, $labelValues, $labelField, $color, $attachmentChartTypeOption) {
    $data = [];

    foreach ($view->result as $row_number => $row) {
      $numberFields = 0;
      $rowData = [];
      foreach ($labelValues as $fieldId => $rowDataValue) {
        $alter_text = $view->field[$labelField]->options['alter']['alter_text'];
        if ($alter_text) {
            $text = $view->field[$labelField]->options['alter']['text'];
            $tokenized_text = trim(str_replace("\n", '', strip_tags($view->field[$labelField]->tokenizeValue($text, $row_number))));
        }
        $rowData[$numberFields] = [
          'value' => $view->field[$fieldId]->getValue($row),
          'label_field' => ($alter_text) ? $tokenized_text : $view->field[$labelField]->getValue($row),
          'label' => $view->field[$fieldId]->label(),
          // 'label' => $view->display_handler->display['id'], to use display_id
          'color' => $color[$fieldId],
          'type' => $attachmentChartTypeOption,
        ];
        $numberFields++;
      }
      $data[$row_number] = $rowData;
    }

    return $data;
  }

  /**
   * Removes unselected fields
   */

  public static function removeUnselectedFields($valueField) {
    $fieldValues = [];
    foreach ($valueField as $key => $value) {
      if (!empty($value)) {
        $fieldValues[$key] = $value;
      }
    }
    return $fieldValues;
  }

  /**
   * Creates chart data to be used later by visualization frameworks
   */

  public static function createChartableData($data) {
    $chartData = [];
    $categories = [];
    $seriesData = [];

    for ($i = 0; $i < count($data[0]); $i++) {

      $seriesRowData = ['name' => '', 'color' => '', 'type' => '', 'data' => []];
      for ($j = 0; $j < count($data); $j++) {
        $categories[$j] = $data[$j][$i]['label_field'];
        $seriesRowData['name'] = $data[$j][$i]['label'];
        // $seriesRowData['name'] = $data[$j][$i]['label_field'];
        $seriesRowData['type'] = $data[$j][$i]['type'];
        $seriesRowData['color'] = $data[$j][$i]['color'];
        array_push($seriesRowData['data'], ((int)($data[$j][$i]['value'])));
      }
      array_push($seriesData, $seriesRowData);
    }
    $chartData[0] = $categories;
    $chartData[1] = $seriesData;

    return $chartData;
  }

  /**
   * Checks for missing libraries necessary for data visualization
   *
   * @param $moduleName
   * @param $libraryPath
   *
   */
  public static function checkMissingLibrary($moduleName, $libraryPath) {
    $module_path = drupal_get_path('module', $moduleName);
    if (!file_exists($module_path . $libraryPath)) {
      drupal_set_message(t('Charting libraries for ' . $moduleName . ' might 
      not be installed. Run \'composer install\' for ' . $moduleName . ' sub-module.'), 'error');
    }
  }

}
