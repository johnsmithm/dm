<?php

namespace Drupal\charts\Plugin\views\display;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\Attachment;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Display plugin to attach multiple chart configurations to the same chart.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "chart_extension",
 *   title = @Translation("Chart attachment"),
 *   help = @Translation("Display that produces a chart."),
 *   theme = "views_view_charts",
 *   contextual_links_locations = {""}
 * )
 *
 */
class ChartsPluginDisplayChart extends Attachment {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['style_plugin']['default'] = 'chart';
    $options['inherit_yaxis'] = ['default' => '1'];

    return $options;

  }

  public function execute() {
    return $this->view->render($this->display['id']);
  }

  /**
   * Provide the summary for page options in the views UI.
   *
   * This output is returned as an array.
   *
   * @param $categories
   * @param $options
   */
  public function optionsSummary(&$categories, &$options) {
    // It is very important to call the parent function here:
    parent::optionsSummary($categories, $options);

    $categories['attachment'] = [
      'title' => t('Chart settings'),
      'column' => 'second',
      'build' => ['#weight' => -10,],
    ];
    $displays = array_filter($this->getOption('displays'));
    if (count($displays) > 1) {
      $attach_to = $this->t('Multiple displays');
    } elseif (count($displays) == 1) {
      $display = array_shift($displays);
      if ($display = $this->view->storage->getDisplay($display)) {
        $attach_to = $display['display_title'];
      }
    }
    if (!isset($attach_to)) {
      $attach_to = $this->t('Not defined');
    }
    $options['displays'] = [
      'category' => 'attachment',
      'title' => $this->t('Parent display'),
      'value' => $attach_to,
    ];

    $options['inherit_yaxis'] = [
      'category' => 'attachment',
      'title' => $this->t('Axis settings'),
      'value' => $this->getOption('inherit_yaxis') ? t('Use primary Y-axis') : t('Create secondary axis'),
    ];

    unset($options['attachment_position']);
    unset($options['inherit_pager']);

  }

  /**
   * Provide the default form for setting options.
   *
   * @param $form
   * @param FormStateInterface $form_state
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    switch ($form_state->get('section')) {
      case 'displays':
        $form['#title'] .= t('Parent display');
        break;

      case 'inherit_yaxis':
        $form['#title'] .= t('Axis settings');
        $form['inherit_yaxis'] = [
          '#title' => t('Y-Axis settings'),
          '#type' => 'radios',
          '#options' => [
            1 => t('Inherit primary of parent display'),
            0 => t('Create a secondary axis'),
          ],
          '#default_value' => $this->getOption('inherit_yaxis'),
          '#description' => t('In most charts, the X and Y axis from the parent display are both shared with each attached child chart. However, if this chart is going to use a different unit of measurement, a secondary axis may be added on the opposite side of the normal Y-axis.'),
        ];
        break;
    }

  }

  /**
   * Perform any necessary changes to the form values prior to storage.
   * There is no need for this function to actually store the data.
   *
   * @param $form
   * @param FormStateInterface $form_state
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    // It is very important to call the parent function here:
    parent::submitOptionsForm($form, $form_state);
    $section = $form_state->get('section');
    switch ($section) {
      case 'displays':
        $form_state->setValue($section, array_filter($form_state->getValue($section)));
        break;

      case 'inherit_arguments':
      case 'inherit_exposed_filters':
      case 'inherit_yaxis':
        $this->setOption($section, $form_state->getValue($section));
        break;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function attachTo(ViewExecutable $view, $display_id, array &$build) {

    $displays = $this->getOption('displays');
    if (empty($displays[$display_id])) {
      return;
    }

    if (!$this->access()) {
      return;
    }

  }

}
