Charts
======

The Charts module provides a unified format to build any kind of chart with any
chart provider.

Each chart solution found on internet, such as Google Charts or Highcharts,
has a specific data scheme. Its very hard and even impossible to build a unique
chart data scheme that would be used in more that one chart provider. Or users
get bound to a solution forever. Or they have to rewrite all exported data
again.

That's why Charts is so great. It uses a standard data scheme to describe charts
data, and through filters, it automatically converts to each solution. You can
change to another solution at anytime.

The Chart schema is very similar to Drupal's Form API schema.

Chart Providers
---------------

Out of the Box, you will be able to use 3 chart solutions. Each of them has
particular advantages and disadvantages.

* C3: This library is a D3-based reusable chart library makes it easy to
  generate D3-based charts by wrapping the code required to construct the
  entire chart. You don't need to write D3 code any more.

* Google Charts: This library does not require any external downloads. It
  generates interactive charts using SVG and VML.

* Highcharts: This library is one of the premier solutions for generating
  charts. Although it is very powerful and aesthetically pleasing with smooth
  animations, it requires a commercial license. It's free for non-commercial
  use. See http://www.highcharts.com

Installing Libraries
---------------------

The 8.x version of Charts is designed to be used with Composer. After you
enable a sub-module (charts_c3, charts_google, or charts_highcharts), you need
to download the library. Here's how I would do it for C3 (starting in my Drupal
root directory:

cd modules/charts/modules/charts_c3
composer install

This will set up the following directory structure within your charts_c3
directory:

vendor
 - cthree
   - css
     - c3.min.css
   - c3.min.js
 - dthree
     - d3.v3.min.js

There are numerous tutorials on Drupal.org and elsewhere on the web if you are
looking for more information about how to use Composer with Drupal 8.

Creating Charts in the UI
-------------------------

This module provides a configuration page at admin/config/content/charts. You
may set site-wide defaults on this page (for example set the default color
scheme).

In order to actually create a chart through the UI, you'll need to use Views
module.

- Create a new view:
  Visit admin/structure/views/add and select the display format of "Chart" for
  your new page or block.

- Add a label field:
  Under the "Fields" section, add a field you would like to be used as labels
  along one axis of the chart (or slices of the pie).

- Add data fields:
  Now a second field that will be used to determine the data values. If you
  are visualizing an Event content type, this field might be
  field_number_attendees. The label you give this field will be used in the
  chart's legend to represent this series. Do this again for
  each different quantity you would like to chart. Note that some charts
  (e.g. Pie) only support a single data column.

- Configure the chart display:
  Click on the "Settings" link in the Format section to configure the chart.
  Select your chart type. Some options may not be available to all chart types
  and will be adjusted based on the type selected.

- Save your view.

Tip: You may find it easier to start with a "Table" display and convert it to a
chart display after setting up the data. It can be easier to visualize what
the result of the chart will be if it's been laid out in a table first.

Creating Multiple Series and Combo Charts in the UI
---------------------------------------------------

A major difference between the Drupal 7 and Drupal 8 versions of this module is
that the Drupal 8 module uses a Chart Attachment plugin for creating a separate
chart series that can be attached to a parent display.

As of the -alpha release, there is a lot of work needed here. The following are
identified questions/issues, which we would like your help with:

1) Is a Chart Attachment (previously Chart Add-on) display still needed with
the improvements made to the module in Drupal 8?
2) Todo: determine how to respect the "Create Secondary Axis" option on the
Chart Attachment
3) Todo: determine how to respect the selection of a different chart type (for
example, the display could be a bar graph and the attachment could be a line
graph)

Support
-------

For bug reports and feature requests please use the Drupal.org issue tracker:
http://drupal.org/project/issues/charts.

We welcome your support in improving code documentation, tests, and providing
example use-cases not addressed by the existing module.

If you are interested in creating your own sub-module for a library not
currently supported (for example, Flot), please contact @andileco
