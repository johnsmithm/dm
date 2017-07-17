<?php

namespace Drupal\charts\Services;

interface ChartAttachmentServiceInterface {
  /**
   * @return $attachmentViews an array of different views.
   */

  public function getAttachmentViews();

  /**
   * @param $attachmentViews is an array of views.
   */

  public function setAttachmentViews($attachmentViews);

}
