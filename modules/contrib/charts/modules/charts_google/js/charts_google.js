/**
 * @file
 * JavaScript integration between Google and Drupal.
 */
(function ($) {
  'use strict';

  Drupal.behaviors.chartsGooglecharts = {
    attach: function (context, settings) {
      google.charts.load('current', {packages: ['corechart']});

      $('.charts-google').each(function (param) {
        var chartId = $(this).attr('id');
        $('#' + chartId).once().each(function () {
          if ($(this).attr('data-chart')) {
            var dataTable = $(this).attr('data-chart');
            var googleChartOptions = $(this).attr('google-options');
            var googleChartType = $(this).attr('google-chart-type');
            google.charts.setOnLoadCallback(draw(googleChartType, dataTable, googleChartOptions));
          }
        });
        function draw(chartType, dataTable, googleChartOptions) {

          return function () {
            var data = google.visualization.arrayToDataTable(JSON.parse(dataTable));
            var googleChartTypeObject = JSON.parse(chartType);
            var googleChartTypeFormatted = googleChartTypeObject.type;
            var chart;
            switch (googleChartTypeFormatted) {
              case 'BarChart':
                chart = new google.visualization.BarChart(document.getElementById(chartId));
                break;
              case 'ColumnChart':
                chart = new google.visualization.ColumnChart(document.getElementById(chartId));
                break;
              case 'PieChart':
                chart = new google.visualization.PieChart(document.getElementById(chartId));
                break;
              case 'ScatterChart':
                chart = new google.visualization.ScatterChart(document.getElementById(chartId));
                break;
              case 'AreaChart':
                chart = new google.visualization.AreaChart(document.getElementById(chartId));
                break;
              case 'LineChart':
                chart = new google.visualization.LineChart(document.getElementById(chartId));
            }
            chart.draw(data, JSON.parse(googleChartOptions));
          };
        }
      });


    }
  };
}(jQuery));
