<?php

/**
 * @file
 * Menu callbacks for Charts module.
 */
use Drupal\charts\Theme\ChartsInterface;

/**
 * Module settings page. Users can set the default layout of their charts.
 *
 * @param $form
 *   The form array to which this form will be added.
 * @param array $defaults
 *   An array of existing values which will be used to populate defaults.
 * @param array $field_options
 *   An array of key => value names of fields within this chart.
 * @param array $parents
 *   If all the contents of this form should be parented under a particular
 *   namespace, an array of parent names that will be prepended to each
 *   element's #parents property.
 *
 * @return mixed The form with the chart settings added.
 * The form with the chart settings added.
 */

/**
 * Retrieve a list of all charting libraries available.
 *
 * @see hook_charts_info()
 */
function charts_info() {

  $charts_info = [];
  $chart_modules = Drupal::moduleHandler()->getImplementations('charts_info');
  foreach ($chart_modules as $module) {
    $module_charts_info = Drupal::moduleHandler()
      ->invoke($module, 'charts_info');
    foreach ($module_charts_info as $chart_library => $chart_library_info) {
      $module_charts_info[$chart_library]['module'] = $module;
    }
    $charts_info = array_merge($charts_info, $module_charts_info);
  }
  Drupal::moduleHandler()->alter('charts_info', $charts_info);

  return $charts_info;
}

/**
 * Retrieve a list of all chart types available.
 *
 * @see hook_charts_type_info()
 */
function charts_type_info() {

  $charts_type_info = Drupal::moduleHandler()->invokeAll('charts_type_info');

  foreach ($charts_type_info as $chart_type => $chart_type_info) {
    $charts_type_info[$chart_type] += [
      'label' => '',
      'axis' => ChartsInterface::CHARTS_DUAL_AXIS,
      'axis_inverted' => FALSE,
      'stacking' => FALSE,
    ];
  }

  Drupal::moduleHandler()->alter('charts_type_info', $charts_type_info);
  return $charts_type_info;
}

/**
 * Retrieve a specific chart type.
 *
 * @param string $chart_type
 * The type of chart selected for display.
 *
 * @return mixed
 * If not false, returns an array of values from charts_charts_type_info.
 */
function charts_get_type($chart_type) {
  $types = charts_type_info();
  return ($types[$chart_type]) ? $types[$chart_type] : FALSE;
}

/**
 * Implements hook_charts_type_info().
 */
function charts_charts_type_info() {
  $chart_types['pie'] = [
    'label' => t('Pie'),
    'axis' => ChartsInterface::CHARTS_SINGLE_AXIS,
  ];
  $chart_types['bar'] = [
    'label' => t('Bar'),
    'axis' => ChartsInterface::CHARTS_DUAL_AXIS,
    'axis_inverted' => TRUE,
    'stacking' => TRUE,
  ];
  $chart_types['column'] = [
    'label' => t('Column'),
    'axis' => ChartsInterface::CHARTS_DUAL_AXIS,
    'stacking' => TRUE,
  ];
  $chart_types['line'] = [
    'label' => t('Line'),
    'axis' => ChartsInterface::CHARTS_DUAL_AXIS,
  ];
  $chart_types['area'] = [
    'label' => t('Area'),
    'axis' => ChartsInterface::CHARTS_DUAL_AXIS,
    'stacking' => TRUE,
  ];
  $chart_types['scatter'] = [
    'label' => t('Scatter'),
    'axis' => ChartsInterface::CHARTS_DUAL_AXIS,
  ];
  return $chart_types;
}

/**
 * Default colors used in all libraries.
 */
function charts_default_colors() {
  return [
    '#2f7ed8',
    '#0d233a',
    '#8bbc21',
    '#910000',
    '#1aadce',
    '#492970',
    '#f28f43',
    '#77a1e5',
    '#c42525',
    '#a6c96a',
  ];
}

/**
 * Recursive function to trim out empty options that aren't used.
 *
 * @param array $array
 * Array may contain empty options.
 */
function charts_trim_array(&$array) {
  foreach ($array as $key => &$value) {
    if (is_array($value)) {
      charts_trim_array($value);
    } elseif (is_null($value) || (is_array($value) && count($value) === 0)) {
      unset($array[$key]);
    }
  }
}

/**
 * Recursive function to cast integer values.
 *
 * @param mixed $element
 * Cast options to integers to avoid redundant library fixing problems.
 */
function charts_cast_element_integer_values(&$element) {
  $integer_options = [
    // Chart options.
    '#title_font_size',
    '#font_size',
    '#legend_title_font_size',
    '#legend_font_size',
    '#width',
    '#height',
    // Axis options.
    '#title_font_size',
    '#labels_font_size',
    '#labels_rotation',
    '#max',
    '#min',
    // Data options.
    '#decimal_count',
  ];

  foreach ($element as $property_name => $value) {
    if (is_array($element[$property_name])) {
      charts_cast_element_integer_values($element[$property_name]);
    } elseif ($property_name && in_array($property_name, $integer_options)) {
      $element[$property_name] = (is_null($element[$property_name]) || strlen($element[$property_name]) === 0)
        ? NULL : (int)$element[$property_name];
    }
  }
}

/**
 * @param $form
 * @param array $defaults
 * @param array $field_options
 * @param array $parents
 *
 * @return mixed
 */
function charts_settings_form($form, $defaults = [], $field_options = [], $parents = []) {
  // Ensure all defaults are set.
  $options = array_merge(charts_default_settings(), $defaults);

  // Get a list of available chart libraries.
  $charts_info = charts_info();
  $library_options = [];
  foreach ($charts_info as $library_name => $library_info) {
    if (Drupal::moduleHandler()->moduleExists($charts_info[$library_name]['module'])) {
      $library_options[$library_name] = $library_info['label'];
    }
  }
  if (count($library_options) == 0) {
    drupal_set_message(t('There are no enabled charting libraries. Please enable a Charts sub-module.'));
  }
  $form['library'] = [
    '#title' => t('Charting library'),
    '#type' => 'select',
    '#options' => $library_options,
    '#default_value' => $options['library'],
    '#required' => TRUE,
    '#access' => count($library_options) > 0,
    '#attributes' => ['class' => ['chart-library-select']],
    '#weight' => -15,
    '#parents' => array_merge($parents, ['library']),
  ];

  $chart_types = charts_type_info();
  $type_options = [];
  foreach ($chart_types as $chart_type => $chart_type_info) {
    $type_options[$chart_type] = $chart_type_info['label'];
  }
  $form['type'] = [
    '#title' => t('Chart type'),
    '#type' => 'radios',
    '#default_value' => $options['type'],
    '#options' => $type_options,
    '#required' => TRUE,
    '#weight' => -20,
    '#attributes' => [
      'class' => [
        'chart-type-radios',
        'container-inline',
      ],
    ],
    '#parents' => array_merge($parents, ['type']),
  ];

  // Set data attributes to identify special properties of different types.
  foreach ($chart_types as $chart_type => $chart_type_info) {
    if ($chart_type_info['axis_inverted']) {
      $form['type'][$chart_type]['#attributes']['data-axis-inverted'] = TRUE;
    }
    if ($chart_type_info['axis'] === ChartsInterface::CHARTS_SINGLE_AXIS) {
      $form['type'][$chart_type]['#attributes']['data-axis-single'] = TRUE;
    }
  }

  if ($field_options) {
    $first_field = key($field_options);

    $form['#theme'] = 'charts_settings_fields';

    $form['fields'] = [
      '#title' => t('Charts fields'),
      '#type' => 'fieldset',
    ];
    $form['fields']['label_field'] = [
      '#type' => 'radios',
      '#title' => t('Label field'),
      '#options' => $field_options + ['' => t('No label field')],
      '#default_value' => isset($options['label_field']) ? $options['label_field'] : $first_field,
      '#weight' => -10,
      '#parents' => array_merge($parents, ['label_field']),
    ];
    $form['fields']['table'] = [
      '#type' => 'table',
      '#header' => [t('Field Name'), t('Provides Data'), t('Color')],
      '#tabledrag' => TRUE,
    ];

    $field_count = 0;
    foreach ($field_options as $field_name => $field_label) {
      $form['fields']['table'][$field_count]['label_label'] = [
        '#type' => 'label',
        '#title' => $field_label,
        '#column' => 'one',
      ];
      $form['fields']['table'][$field_count]['data_fields'][$field_name] = [
        '#type' => 'checkbox',
        '#title' => $field_name,
        '#default_value' => $options['data_fields'][$field_name],
        '#return_value' => $field_name,
        '#weight' => -9,
        '#states' => [
          'disabled' => [
            ':input[name="style_options[label_field]"]' => ['value' => $field_name],
          ],
        ],
        '#parents' => array_merge($parents, ['data_fields', $field_name]),
        '#column' => 'two',
      ];
      $form['fields']['table'][$field_count]['field_colors'][$field_name] = [
        '#type' => 'textfield',
        '#attributes' => ['TYPE' => 'color'],
        '#size' => 10,
        '#maxlength' => 7,
        '#theme_wrappers' => [],
        '#default_value' => !empty($options['field_colors'][$field_name]) ? $options['field_colors'][$field_name] : $options['colors'][$field_count],
        '#parents' => array_merge($parents, ['field_colors', $field_name]),
        '#column' => 'three',
      ];
      $field_count++;

    }

  }
  $form['display'] = [
    '#title' => t('Display'),
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  ];
  $form['display']['title'] = [
    '#title' => t('Chart title'),
    '#type' => 'textfield',
    '#default_value' => $options['title'],
    '#parents' => array_merge($parents, ['title']),
  ];
  $form['display']['title_position'] = [
    '#title' => t('Title position'),
    '#type' => 'select',
    '#options' => [
      '' => t('None'),
      'out' => t('Outside'),
      'in' => t('Inside'),
    ],
    '#default_value' => $options['title_position'],
    '#parents' => array_merge($parents, ['title_position']),
  ];

  $form['display']['tooltips'] = [
    '#title' => t('Tooltips'),
    '#type' => 'select',
    '#options' => [
      '' => t('Disabled'),
      'TRUE' => t('Enabled'),
    ],
    '#description' => t('Show data details on mouse over? Note: unavailable for print or on mobile devices.'),
    '#default_value' => $options['tooltips'],
    '#parents' => array_merge($parents, ['tooltips']),
  ];

  $form['display']['data_labels'] = [
    '#title' => t('Data labels'),
    '#type' => 'select',
    '#options' => [
      '' => t('Disabled'),
      'TRUE' => t('Enabled'),
    ],
    '#default_value' => $options['data_labels'],
    '#description' => t('Show data details as labels on chart? Note: recommended for print or on mobile devices.'),
    '#parents' => array_merge($parents, ['data_labels']),
  ];

  $form['display']['legend_position'] = [
    '#title' => t('Legend position'),
    '#type' => 'select',
    '#options' => [
      '' => t('None'),
      'top' => t('Top'),
      'right' => t('Right'),
      'bottom' => t('Bottom'),
      'left' => t('Left'),
    ],
    '#default_value' => $options['legend_position'],
    '#parents' => array_merge($parents, ['legend_position']),
  ];

  $form['display']['background'] = [
    '#title' => t('Background color'),
    '#type' => 'textfield',
    '#size' => 10,
    '#maxlength' => 7,
    '#attributes' => ['placeholder' => t('transparent')],
    '#description' => t('Leave blank for a transparent background.'),
    '#default_value' => $options['background'],
    '#parents' => array_merge($parents, ['background']),
  ];

  $form['display']['dimensions'] = [
    '#title' => t('Dimensions'),
    '#theme_wrappers' => ['form_element'],
    '#description' => t('If dimensions are left empty, the chart will fill its containing element.'),
  ];
  $form['display']['dimensions']['width'] = [
    '#type' => 'textfield',
    '#attributes' => [
      'TYPE' => 'number',
      'step' => 1,
      'min' => 0,
      'max' => 9999,
      'placeholder' => t('auto'),
    ],
    '#default_value' => $options['width'],
    '#size' => 8,
    '#suffix' => ' x ',
    '#theme_wrappers' => [],
    '#parents' => array_merge($parents, ['width']),
  ];
  $form['display']['dimensions']['height'] = [
    '#type' => 'textfield',
    '#attributes' => [
      'TYPE' => 'number',
      'step' => 1,
      'min' => 0,
      'max' => 9999,
      'placeholder' => t('auto'),
    ],
    '#default_value' => $options['height'],
    '#size' => 8,
    '#suffix' => ' px',
    '#theme_wrappers' => [],
    '#parents' => array_merge($parents, ['height']),
  ];

  $form['xaxis'] = [
    '#title' => t('Horizontal axis'),
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#attributes' => ['class' => ['chart-xaxis']],
  ];
  $form['xaxis']['xaxis_title'] = [
    '#title' => t('Custom title'),
    '#type' => 'textfield',
    '#default_value' => $options['xaxis_title'],
    '#parents' => array_merge($parents, ['xaxis_title']),
  ];
  $form['xaxis']['labels_rotation'] = [
    '#title' => t('Labels rotation'),
    '#type' => 'select',
    '#options' => [
      0 => t('0°'),
      30 => t('30°'),
      45 => t('45°'),
      60 => t('60°'),
      90 => t('90°'),
    ],
    // This is only shown on non-inverted charts.
    '#attributes' => ['class' => ['axis-inverted-hide']],
    '#default_value' => $options['xaxis_labels_rotation'],
    '#parents' => array_merge($parents, ['xaxis_labels_rotation']),
  ];

  $form['yaxis'] = [
    '#title' => t('Vertical axis'),
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#attributes' => ['class' => ['chart-yaxis']],
  ];
  $form['yaxis']['title'] = [
    '#title' => t('Custom title'),
    '#type' => 'textfield',
    '#default_value' => $options['yaxis_title'],
    '#parents' => array_merge($parents, ['yaxis_title']),
  ];
  $form['yaxis']['minmax'] = [
    '#title' => t('Value range'),
    '#theme_wrappers' => ['form_element'],
  ];
  $form['yaxis']['minmax']['min'] = [
    '#type' => 'textfield',
    '#attributes' => [
      'TYPE' => 'number',
      'max' => 999999,
      'placeholder' => t('Minimum'),
    ],
    '#default_value' => $options['yaxis_min'],
    '#size' => 12,
    '#parents' => array_merge($parents, ['yaxis_min']),
    '#suffix' => ' ',
    '#theme_wrappers' => [],
  ];
  $form['yaxis']['minmax']['max'] = [
    '#type' => 'textfield',
    '#attributes' => [
      'TYPE' => 'number',
      'max' => 999999,
      'placeholder' => t('Maximum'),
    ],
    '#default_value' => $options['yaxis_max'],
    '#size' => 12,
    '#parents' => array_merge($parents, ['yaxis_max']),
    '#theme_wrappers' => [],
  ];
  $form['yaxis']['prefix'] = [
    '#title' => t('Value prefix'),
    '#type' => 'textfield',
    '#default_value' => $options['yaxis_prefix'],
    '#size' => 12,
    '#parents' => array_merge($parents, ['yaxis_prefix']),
  ];
  $form['yaxis']['suffix'] = [
    '#title' => t('Value suffix'),
    '#type' => 'textfield',
    '#default_value' => $options['yaxis_suffix'],
    '#size' => 12,
    '#parents' => array_merge($parents, ['yaxis_suffix']),
  ];
  $form['yaxis']['decimal_count'] = [
    '#title' => t('Decimal count'),
    '#type' => 'textfield',
    '#attributes' => [
      'TYPE' => 'number',
      'step' => 1,
      'min' => 0,
      'max' => 20,
      'placeholder' => t('auto'),
    ],
    '#default_value' => $options['yaxis_decimal_count'],
    '#size' => 5,
    '#description' => t('Enforce a certain number of decimal-place digits in displayed values.'),
    '#parents' => array_merge($parents, ['yaxis_decimal_count']),
  ];
  $form['yaxis']['labels_rotation'] = [
    '#title' => t('Labels rotation'),
    '#type' => 'select',
    '#options' => [
      0 => t('0°'),
      30 => t('30°'),
      45 => t('45°'),
      60 => t('60°'),
      90 => t('90°'),
    ],
    // This is only shown on inverted charts.
    '#attributes' => ['class' => ['axis-inverted-show']],
    '#default_value' => $options['yaxis_labels_rotation'],
    '#parents' => array_merge($parents, ['yaxis_labels_rotation']),
  ];

  return $form;
}

/**
 * Menu callback; Configure the site-wide defaults for charts.
 *
 * @param $form
 * @param $form_state
 * Standard parameters for a form.
 *
 * @return mixed
 *
 */
function charts_default_settings_form($form, $form_state) {
  $defaults = \Drupal::state()
    ->get('charts_default_settings', []);
  $defaults += charts_default_settings();
  $field_options = [];
  $parents = ['charts_default_settings'];

  // Add help.
  $form['help'] = [
    '#type' => 'markup',
    '#markup' => '<p>' . t('The settings on this page are used to set <strong>default</strong> settings. They do not affect existing charts. To make a new chart, <a href="!views">create a new view</a> and select the display format of "Chart".', ['!views' => url('admin/structure/views/add')]) . '</p>',
    '#weight' => -100,
  ];

  // Reuse the global settings form for defaults, but remove JS classes.
  $form = charts_settings_form($form, $defaults, $field_options, $parents);
  $form['xaxis']['#attributes']['class'] = [];
  $form['yaxis']['#attributes']['class'] = [];
  $form['display']['colors']['#prefix'] = NULL;
  $form['display']['colors']['#suffix'] = NULL;

  // Put settings into vertical tabs.
  $form['display']['#group'] = 'defaults';
  $form['xaxis']['#group'] = 'defaults';
  $form['yaxis']['#group'] = 'defaults';
  $form['defaults'] = [
    '#type' => 'vertical_tabs',
  ];

  // Add submit buttons and normal saving behavior.
  $form['actions']['#type'] = 'actions';
  $form['actions']['submit'] = [
    '#type' => 'submit',
    '#value' => t('Save defaults'),
  ];

  return $form;
}

/**
 * Submit handler for charts_default_settings_form().
 * @param $form
 * @param $form_state
 */
function charts_default_settings_form_submit($form, $form_state) {
  \Drupal::state()
    ->set('charts_default_settings', $form_state['values']['charts_default_settings']);
}

/**
 * Provides default options used by charts_settings_form().
 */
function charts_default_settings() {
  $defaults = [];
  $defaults['type'] = 'pie';
  $defaults['library'] = NULL;
  $defaults['label_field'] = NULL;
  $defaults['data_fields'] = NULL;
  $defaults['field_colors'] = NULL;
  $defaults['title'] = '';
  $defaults['title_position'] = 'out';
  $defaults['data_labels'] = FALSE;
  $defaults['legend'] = TRUE;
  $defaults['legend_position'] = 'right';
  $defaults['colors'] = charts_default_colors();
  $defaults['background'] = '';
  $defaults['tooltips'] = TRUE;
  $defaults['tooltips_use_html'] = FALSE;
  $defaults['width'] = NULL;
  $defaults['height'] = NULL;
  $defaults['xaxis_title'] = '';
  $defaults['xaxis_labels_rotation'] = 0;
  $defaults['yaxis_title'] = '';
  $defaults['yaxis_min'] = '';
  $defaults['yaxis_max'] = '';
  $defaults['yaxis_prefix'] = '';
  $defaults['yaxis_suffix'] = '';
  $defaults['yaxis_decimal_count'] = '';
  $defaults['yaxis_labels_rotation'] = 0;

  Drupal::moduleHandler()->alter('charts_default_settings', $defaults);
  return $defaults;
}

