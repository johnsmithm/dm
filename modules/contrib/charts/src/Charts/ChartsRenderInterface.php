<?php

namespace Drupal\charts\Charts;


interface ChartsRenderInterface {
  public function charts_render_charts($options, $categories = [], $seriesData = [], $attachmentDisplayOptions = [], &$variables, $chartId);
}
