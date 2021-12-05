<?php

namespace Drupal\guest_book\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the Guest book module.
 */
class GuestBookController extends ControllerBase {

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function GuestBookPage(): array {
    $form = \Drupal::formBuilder()->getForm('Drupal\guest_book\Form\GuestBookForm');
    return [
      $form,
    ];
  }

};
