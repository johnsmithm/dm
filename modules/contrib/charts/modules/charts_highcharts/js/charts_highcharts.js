/**
 * @file
 * JavaScript integration between Highcharts and Drupal.
 */
(function ($) {
  'use strict';

  Drupal.behaviors.chartsHighcharts = {
    attach: function (context, settings) {

      $('.charts-highchart').once().each(function () {
        if ($(this).attr('data-chart')) {
          var highcharts = $(this).attr('data-chart');
          $(this).highcharts(JSON.parse(highcharts));
        }
      });
    }
  };
}(jQuery));
