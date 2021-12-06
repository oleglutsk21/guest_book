<?php

namespace Drupal\guest_book\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File;

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
    $commentForm = \Drupal::formBuilder()->getForm('\Drupal\guest_book\Form\GuestBookForm');
    return [
      '#theme' => 'comments-list',
      '#form' => $commentForm,
      '#row' => $this->getComments(),
    ];
  }

  public function getComments(): array {

    // get data from database
    $query = \Drupal::database()->select('guest_book', 'gb');
    $query->fields('gb', ['id', 'user_name', 'user_email', 'user_phone', 'user_message', 'user_avatar', 'user_image', 'date']);
    $query->orderBy('gb.date', 'DESC');
    $result = $query->execute()->fetchAll();
    $rows = [];

    foreach ($result as $data) {
      $userAvatarUri = File::load($data->user_avatar);

      if (is_null($userAvatarUri)) {
        $userAvatarUri = '/modules/custom/guest_book/file/avatar_default.png';
      }
      else {
        $userAvatarUri = $userAvatarUri->getFileUri();
      }

      $userAvatarUrl = file_create_url($userAvatarUri);
      $userAvatar = [
        '#theme' => 'image',
        '#uri' => $userAvatarUri,
        '#attributes' => [
          'class' => 'user_avatar',
          'alt' => $data->user_name . ' avatar',
          'width' => 100,
        ],
      ];

      $userImageUri = File::load($data->user_image);
      $userImageUrl = '';

      if (is_null($userImageUri)) {
        $userImage = NULL;
      }
      else {
        $userImageUri = $userImageUri->getFileUri();
        $userImageUrl = file_create_url($userImageUri);

        $userImage = [
          '#theme' => 'image',
          '#uri' => $userImageUri,
          '#attributes' => [
            'class' => 'user_image',
            'alt' => $data->user_name . ' comment image',
            'width' => 200,
          ],
        ];
      }

      //get data
      $rows[] = [
        'id' => $data->id,
        'user_name' => $data->user_name,
        'user_email' => $data->user_email,
        'user_phone' => $data->user_phone,
        'user_message' => $data->user_message,
        'user_avatar' => $userAvatar,
        'user_image' => $userImage,
        'user_avatar_url' => $userAvatarUrl,
        'user_image_url' => $userImageUrl,
        'date' => date('Y-m-d H:i:s', $data->date),
      ];
    }

    return $rows;
  }

}
