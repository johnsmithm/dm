<?php

namespace Drupal\charts\Form;

use Drupal\charts\Theme\ChartsInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ChartsConfigForm extends ConfigFormBase {
  protected $moduleHandler;

  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'), $container->get('module_handler'));
  }

  public function getFormId() {
    return 'charts_form';
  }

  protected function getEditableConfigNames() {
    return ['charts.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('charts.settings');

    $parents = ['charts_default_settings'];
    $default_config = $config->get('charts_default_settings');
    if ($default_config == NULL) {
      $defaults = [] + $this->charts_default_settings();
    } else {
      $defaults = $default_config + $this->charts_default_settings();
    }

    $field_options = [];
    $form['help'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('The settings on this page are used to set
        <strong>default</strong> settings. They do not affect existing charts.
        To make a new chart, <a href="@create">create a new view</a> and select
        the display format of "Chart".', ['@create' => Url::fromRoute('views_ui.add')->toString()])
        . '</p>',
      '#weight' => -100,
    ];
    // Reuse the global settings form for defaults, but remove JS classes.
    $form = $this->chartsSettingsForm($form, $defaults, $field_options, $parents);
    $form['xaxis']['#attributes']['class'] = [];
    $form['yaxis']['#attributes']['class'] = [];
    $form['display']['colors']['#prefix'] = NULL;
    $form['display']['colors']['#suffix'] = NULL;
    // Put settings into vertical tabs.
    $form['display']['#group'] = 'defaults';
    $form['xaxis']['#group'] = 'defaults';
    $form['yaxis']['#group'] = 'defaults';
    $form['defaults'] = ['#type' => 'vertical_tabs'];
    // Add submit buttons and normal saving behavior.
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save defaults'),
    ];

    return $form;
  }

  public function charts_default_settings() {
    $defaults = [];
    $defaults['type'] = 'pie';
    $defaults['library'] = NULL;
    $defaults['label_field'] = NULL;
    $defaults['data_fields'] = NULL;
    $defaults['field_colors'] = NULL;
    $defaults['title'] = '';
    $defaults['title_position'] = 'out';
    $defaults['legend'] = TRUE;
    $defaults['legend_position'] = 'right';
    $defaults['colors'] = $this->charts_default_colors();
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

    $this->moduleHandler->alter('charts_default_settings', $defaults);

    return $defaults;
  }

  /**
   * Default colors used in all libraries.
   */
  public function charts_default_colors() {
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

  public function chartsSettingsForm($form, $defaults = [], $field_options = [], $parents = []) {
    // Ensure all defaults are set.
    $options = array_merge($this->charts_default_settings(), $defaults);

    $form['#attached']['library'][] = ['charts', 'charts.admin'];

    // Get a list of available chart libraries.
    $charts_info = $this->charts_info();
    $library_options = [];
    foreach ($charts_info as $library_name => $library_info) {
      if ($this->moduleHandler->moduleExists($charts_info[$library_name]['module'])) {
        $library_options[$library_name] = $library_info['label'];
      }
    }
    if (count($library_options) == 0) {
      drupal_set_message($this->t('There are no enabled charting libraries. Please enable a Charts sub-module.'));
    }
    $form['library'] = [
      '#title' => $this->t('Charting library'),
      '#type' => 'select',
      '#options' => $library_options,
      '#default_value' => $options['library'],
      '#required' => TRUE,
      '#access' => count($library_options) > 0,
      '#attributes' => ['class' => ['chart-library-select']],
      '#weight' => -15,
      '#parents' => array_merge($parents, ['library']),
    ];

    // This is a work around will need to revisit this.
    $chart_types = $this->chartsChartsTypeInfo();
    $type_options = [];
    foreach ($chart_types as $chart_type => $chart_type_info) {
      $type_options[$chart_type] = $chart_type_info['label'];
    }

    $form['type'] = [
      '#title' => $this->t('Chart type'),
      '#type' => 'radios',
      '#default_value' => $options['type'],
      '#options' => $type_options,
      '#required' => TRUE,
      '#weight' => -20,
      '#attributes' => [
        'class' => [
          'chart-type-radios',
          'container-inline',
        ]
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
      $form['fields']['#theme'] = 'charts_settings_fields';
      $form['fields']['label_field'] = [
        '#type' => 'radios',
        '#title' => $this->t('Label field'),
        '#options' => $field_options + ['' => $this->t('No label field')],
        '#default_value' => isset($options['label_field']) ? $options['label_field'] : $first_field,
        '#weight' => -10,
        '#parents' => array_merge($parents, ['label_field']),
      ];
      $form['fields']['data_fields'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Data fields'),
        '#options' => $field_options,
        '#default_value' => isset($options['data_fields']) ? $options['data_fields'] : array_diff(array_keys($field_options), [$first_field]),
        '#weight' => -9,
        '#parents' => array_merge($parents, ['data_fields']),
      ];
      $color_count = 0;
      foreach ($field_options as $field_name => $field_label) {
        $form['fields']['field_colors'][$field_name] = [
          '#type' => 'textfield',
          '#attributes' => ['TYPE' => 'color'],
          '#size' => 10,
          '#maxlength' => 7,
          '#theme_wrappers' => [],
          '#default_value' => !empty($options['field_colors'][$field_name]) ? $options['field_colors'][$field_name] : $options['colors'][$color_count],
          '#parents' => array_merge($parents, [
            'field_colors',
            $field_name,
          ]),
        ];
        $color_count++;
      }
    }

    $form['display'] = [
      '#title' => $this->t('Display'),
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['display']['title'] = [
      '#title' => $this->t('Chart title'),
      '#type' => 'textfield',
      '#default_value' => $options['title'],
      '#parents' => array_merge($parents, ['title']),
    ];
    $form['display']['title_position'] = [
      '#title' => $this->t('Title position'),
      '#type' => 'select',
      '#options' => [
        '' => $this->t('None'),
        'out' => $this->t('Outside'),
        'in' => $this->t('Inside'),
      ],
      '#default_value' => $options['title_position'],
      '#parents' => array_merge($parents, ['title_position']),
    ];

    $form['display']['legend_position'] = [
      '#title' => $this->t('Legend position'),
      '#type' => 'select',
      '#options' => [
        '' => $this->t('None'),
        'top' => $this->t('Top'),
        'right' => $this->t('Right'),
        'bottom' => $this->t('Bottom'),
        'left' => $this->t('Left'),
      ],
      '#default_value' => $options['legend_position'],
      '#parents' => array_merge($parents, ['legend_position']),
    ];

    $form['display']['colors'] = [
      '#title' => $this->t('Chart colors'),
      '#theme_wrappers' => ['form_element'],
      '#prefix' => '<div class="chart-colors">',
      '#suffix' => '</div>',
    ];
    for ($color_count = 0; $color_count < 10; $color_count++) {
      $form['display']['colors'][$color_count] = [
        '#type' => 'textfield',
        '#attributes' => ['TYPE' => 'color'],
        '#size' => 10,
        '#maxlength' => 7,
        '#theme_wrappers' => [],
        '#suffix' => ' ',
        '#default_value' => $options['colors'][$color_count],
        '#parents' => array_merge($parents, ['colors', $color_count]),
      ];
    }
    $form['display']['background'] = [
      '#title' => $this->t('Background color'),
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 7,
      '#attributes' => ['placeholder' => $this->t('transparent')],
      '#description' => $this->t('Leave blank for a transparent background.'),
      '#default_value' => $options['background'],
      '#parents' => array_merge($parents, ['background']),
    ];

    $form['display']['dimensions'] = [
      '#title' => $this->t('Dimensions'),
      '#theme_wrappers' => ['form_element'],
      '#description' => $this->t('If dimensions are left empty, the chart will fill its containing element.'),
    ];
    $form['display']['dimensions']['width'] = [
      '#type' => 'textfield',
      '#attributes' => [
        'TYPE' => 'number',
        'step' => 1,
        'min' => 0,
        'max' => 9999,
        'placeholder' => $this->t('auto'),
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
        'placeholder' => $this->t('auto'),
      ],
      '#default_value' => $options['height'],
      '#size' => 8,
      '#suffix' => ' px',
      '#theme_wrappers' => [],
      '#parents' => array_merge($parents, ['height']),
    ];

    $form['xaxis'] = [
      '#title' => $this->t('Horizontal axis'),
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#attributes' => ['class' => ['chart-xaxis']],
    ];
    $form['xaxis']['title'] = [
      '#title' => $this->t('Custom title'),
      '#type' => 'textfield',
      '#default_value' => $options['xaxis_title'],
      '#parents' => array_merge($parents, ['xaxis_title']),
    ];
    $form['xaxis']['labels_rotation'] = [
      '#title' => $this->t('Labels rotation'),
      '#type' => 'select',
      '#options' => [
        0 => $this->t('0°'),
        30 => $this->t('30°'),
        45 => $this->t('45°'),
        60 => $this->t('60°'),
        90 => $this->t('90°'),
      ], // This is only shown on non-inverted charts.
      '#attributes' => ['class' => ['axis-inverted-hide']],
      '#default_value' => $options['xaxis_labels_rotation'],
      '#parents' => array_merge($parents, ['xaxis_labels_rotation']),
    ];

    $form['yaxis'] = [
      '#title' => $this->t('Vertical axis'),
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#attributes' => ['class' => ['chart-yaxis']],
    ];
    $form['yaxis']['title'] = [
      '#title' => $this->t('Custom title'),
      '#type' => 'textfield',
      '#default_value' => $options['yaxis_title'],
      '#parents' => array_merge($parents, ['yaxis_title']),
    ];
    $form['yaxis']['minmax'] = [
      '#title' => $this->t('Value range'),
      '#theme_wrappers' => ['form_element'],
    ];
    $form['yaxis']['minmax']['min'] = [
      '#type' => 'textfield',
      '#attributes' => [
        'TYPE' => 'number',
        'max' => 999999,
        'placeholder' => $this->t('Minimum'),
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
        'placeholder' => $this->t('Maximum'),
      ],
      '#default_value' => $options['yaxis_max'],
      '#size' => 12,
      '#parents' => array_merge($parents, ['yaxis_max']),
      '#theme_wrappers' => [],
    ];
    $form['yaxis']['prefix'] = [
      '#title' => $this->t('Value prefix'),
      '#type' => 'textfield',
      '#default_value' => $options['yaxis_prefix'],
      '#size' => 12,
      '#parents' => array_merge($parents, ['yaxis_prefix']),
    ];
    $form['yaxis']['suffix'] = [
      '#title' => $this->t('Value suffix'),
      '#type' => 'textfield',
      '#default_value' => $options['yaxis_suffix'],
      '#size' => 12,
      '#parents' => array_merge($parents, ['yaxis_suffix']),
    ];
    $form['yaxis']['decimal_count'] = [
      '#title' => $this->t('Decimal count'),
      '#type' => 'textfield',
      '#attributes' => [
        'TYPE' => 'number',
        'step' => 1,
        'min' => 0,
        'max' => 20,
        'placeholder' => $this->t('auto'),
      ],
      '#default_value' => $options['yaxis_decimal_count'],
      '#size' => 5,
      '#description' => $this->t('Enforce a certain number of decimal-place digits in displayed values.'),
      '#parents' => array_merge($parents, ['yaxis_decimal_count']),
    ];
    $form['yaxis']['labels_rotation'] = [
      '#title' => $this->t('Labels rotation'),
      '#type' => 'select',
      '#options' => [
        0 => $this->t('0°'),
        30 => $this->t('30°'),
        45 => $this->t('45°'),
        60 => $this->t('60°'),
        90 => $this->t('90°'),
      ],
      '#attributes' => [
        'class' => ['axis-inverted-show']
      ],
      '#default_value' => $options['yaxis_labels_rotation'],
      '#parents' => array_merge($parents, ['yaxis_labels_rotation']),
    ];

    return $form;
  }

  /**
   * @return array
   */
  public function charts_info() {
    $charts_info = [];
    foreach ($this->moduleHandler->getImplementations('charts_info') as $module) {
      $module_charts_info = $this->moduleHandler->invoke($module, 'charts_info');
      foreach ($module_charts_info as $chart_library => $chart_library_info) {
        $module_charts_info[$chart_library]['module'] = $module;
      }
      $charts_info = array_merge($charts_info, $module_charts_info);
    }

    $this->moduleHandler->alter('charts_info', $charts_info);

    return $charts_info;
  }

  /**
   * @return mixed
   */
  public function chartsChartsTypeInfo() {
    $chart_types['pie'] = [
      'label' => $this->t('Pie'),
      'axis' => ChartsInterface::CHARTS_SINGLE_AXIS,
    ];
    $chart_types['bar'] = [
      'label' => $this->t('Bar'),
      'axis' => ChartsInterface::CHARTS_DUAL_AXIS,
      'axis_inverted' => TRUE,
      'stacking' => TRUE,
    ];
    $chart_types['column'] = [
      'label' => $this->t('Column'),
      'axis' => ChartsInterface::CHARTS_DUAL_AXIS,
      'stacking' => TRUE,
    ];
    $chart_types['line'] = [
      'label' => $this->t('Line'),
      'axis' => ChartsInterface::CHARTS_DUAL_AXIS,
    ];
    $chart_types['area'] = [
      'label' => $this->t('Area'),
      'axis' => ChartsInterface::CHARTS_DUAL_AXIS,
      'stacking' => TRUE,
    ];
    $chart_types['scatter'] = [
      'label' => $this->t('Scatter'),
      'axis' => ChartsInterface::CHARTS_DUAL_AXIS,
    ];

    return $chart_types;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('charts.settings');
    $config->set('charts_default_settings', $form_state->getValue('charts_default_settings'));
    $config->save();
  }

}
