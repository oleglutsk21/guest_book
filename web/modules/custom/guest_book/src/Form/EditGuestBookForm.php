<?php

namespace Drupal\guest_book\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Provides form for edit comments.
 */
class EditGuestBookForm extends FormBase {

  /**
   * {@inheritDoc}
   */
  public function getFormId(): string {
    return 'edit_form';
  }

  protected $userId;
  protected $data;

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $userId = NULL): array {
    $this->userId = \Drupal::routeMatch()->getParameter('id');
    $connection = Database::getConnection();

    $this->data = [];
    if (isset($this->userId)) {
      $query = $connection->select('guest_book', 'gb')
        ->condition('id', $this->userId)
        ->fields('gb');
      $data = $this->data = $query->execute()->fetchAssoc();
    }
    $form = (new GuestBookForm)->buildForm($form, $form_state);
    $form['user_name']['#default_value'] = (isset($data['user_name'])) ? $data['user_name'] : '';
    $form['user_name']['#ajax']['event'] = 'change';
    $form['user_email']['#default_value'] = (isset($data['user_email'])) ? $data['user_email'] : '';
    $form['user_email']['#ajax']['event'] = 'change';
    $form['user_phone']['#default_value'] = (isset($data['user_phone'])) ? $data['user_phone'] : '';
    $form['user_phone']['#ajax']['event'] = 'change';
    $form['user_message']['#default_value'] = (isset($data['user_message'])) ? $data['user_message'] : '';
    $form['user_avatar']['#default_value'][] = (isset($data['user_avatar'])) ? $data['user_avatar'] : '';
    $form['user_image']['#default_value'][] = (isset($data['user_image'])) ? $data['user_image'] : '';
    $form['submit']['#value'] = $this->t('Edit');
    $form['cancel'] = [
      '#type' => 'button',
      '#value' => $this->t('Cancel'),
      '#ajax' => [
        'callback' => '::cancelCallback',
        'event' => 'click',
        'progress' => [
          'type' => 'none',
        ],
      ],
    ];

    return $form;
  }

  /**
   * Provides cancel button on the edit form.
   */
  public function cancelCallback(array $form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new RedirectCommand('/guest-book'));
    \Drupal::messenger()->deleteAll();
    return $response;
  }

  /**
   * Show message when field user_name is valid or not by ajax.
   */
  public function ajaxUserNameValidate(array $form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    if (!preg_match('/^[_A-Za-z0-9- \\+]{2,100}$/', $form_state->getValue('user_name'))) {
      $response->addCommand(
        new MessageCommand($this->t('Name is incorrect.'), '.edit-form .user__name-validation-message', ['type' => 'error'], TRUE)
      );
    }
    else {
      $response->addCommand(new MessageCommand($this->t('Name is correct.'), '.edit-form .user__name-validation-message'));
    }
    return $response;
  }

  /**
   * Show message when field user_email is valid or not by ajax.
   */
  public function ajaxEmailValidate(array $form, FormStateInterface $form_state): AjaxResponse {
    $userEmail = $form_state->getValue('user_email');
    $response  = new AjaxResponse();
    if (!preg_match('/^[_A-Za-z0-9-\\+]*@[A-Za-z0-9-]+(\\.[A-Za-z0-9]+)*(\\.[A-Za-z]{2,})$/', $userEmail)) {
      $response->addCommand(
        new MessageCommand(
          $this->t('Email is incorrect.'),
          '.edit-form .user__email-validation-message', ['type' => 'error'], TRUE)
      );
    }
    else {
      $response->addCommand(
        new MessageCommand('Email is correct.',
          '.edit-form .user__email-validation-message')
      );
    }
    return $response;
  }

  /**
   * Show message when field user_phone is valid or not by ajax.
   */
  public function ajaxPhoneValidate(array $form, FormStateInterface $form_state): AjaxResponse {
    $userPhone = $form_state->getValue('user_phone');
    $response = new AjaxResponse();
    if (!preg_match('/^((\\+)|(00))[0-9]{12}$/', $userPhone)) {
      $response->addCommand(new MessageCommand(
        $this->t('Phone is incorrect.'),
        '.edit-form .user__phone-validation-message', ['type' => 'error'], TRUE));
    }
    else {
      $response->addCommand(new MessageCommand(
          $this->t('Phone is correct.'),
          '.edit-form .user__phone-validation-message',
        )
      );
    }
    return $response;
  }

  /**
   * Show message when field user_phone is valid or not by ajax.
   */
  public function ajaxSubmitForm(array &$form, FormStateInterface $form_state):object {
    \Drupal::messenger()->deleteAll();
    $response = new AjaxResponse();
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new MessageCommand($this->t('The information you entered is incorrect, no message has been edit.'), NULL, ['type' => 'error']));
    }
    else {
      \Drupal::messenger()->addStatus(t('Comment edited.'));
      $response->addCommand(new RedirectCommand('/guest-book'));
    }

    return $response;

  }

  /**
   * Provides form validate function before submit.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    (new GuestBookForm)->validateForm($form, $form_state);
  }

  /**
   * Provides function for change image file status in table file_managed in database.
   */
  public function changeFileStatus($newFile, $currentFile) {
    if ($newFile != $currentFile) {
      \Drupal::database()
        ->update('file_managed')
        ->fields(['status' => 0])
        ->condition('fid', $currentFile)->execute();
    }
  }

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $userAvatar = $form_state->getValue('user_avatar');
    $userImage = $form_state->getValue('user_image');
    $data = [];
    // Save avatar file as Permanent.
    if (is_null($userAvatar[0])) {
      $data['user_avatar'] = 0;
    }
    else {
      $userAvatarFile = File::load($userAvatar[0]);
      $userAvatarFile->setPermanent();
      $userAvatarFile->save();
    }

    // Save image file as Permanent.
    if (is_null($userImage[0])) {
      $data['user_image'] = 0;
    }
    else {
      $userImageFile = File::load($userImage[0]);
      $userImageFile->setPermanent();
      $userImageFile->save();
    }
    $this->changeFileStatus($userAvatar, $this->data['user_avatar']);
    $this->changeFileStatus($userImage, $this->data['user_image']);
    $data = [
      'user_name' => $form_state->getValue('user_name'),
      'user_email' => $form_state->getValue('user_email'),
      'user_phone' => $form_state->getValue('user_phone'),
      'user_message' => $form_state->getValue('user_message'),
      'user_avatar' => $userAvatar[0],
      'user_image' => $userImage[0],
    ];

    $query = \Drupal::database()->update('guest_book');
    $query
      ->fields($data)
      ->condition('id', $this->userId)
      ->execute();

  }

}
