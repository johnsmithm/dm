<?php

namespace Drupal\charts\Services;

/**
 * Class ChartAttachmentService
 *
 * @package Drupal\charts\Services.
 */

class ChartAttachmentService implements ChartAttachmentServiceInterface {

  private $attachmentViews;

  /**
   * @return $attachmentViews an array of different views.
   */
  public function getAttachmentViews() {

    return $this->attachmentViews;
  }

  /**
   * @param $attachmentViews is an array of views.
   */
  public function setAttachmentViews($attachmentViews) {
    $this->attachmentViews = $attachmentViews;
  }

}
