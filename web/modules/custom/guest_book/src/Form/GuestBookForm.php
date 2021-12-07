<?php

namespace Drupal\guest_book\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Provides custom Guest book form class.
 */

class GuestBookForm extends FormBase {

  /**
   * {@inheritdoc }
   */
  public function getFormId(): string {
    return 'guest_book_form';
  }

  /**
   * {@inheritdoc }
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form['user_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your name:'),
      '#required' => TRUE,
      '#placeholder' => $this->t('The name must be between 2 and 100 characters long'),
      '#suffix' => '<div class="user__name-validation-message"></div>',
      '#ajax' => [
        'callback' => '::ajaxUserNameValidate',
        'event' => 'input',
        'progress' => [
          'type' => 'none',
        ],
      ],
      '#attributes'  => [
        'autocomplete' => 'off',
      ],
    ];

    $form['user_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Your email:'),
      '#required' => TRUE,
      '#placeholder' => $this->t(''),
      '#suffix' => '<div class="user__email-validation-message"></div>',
      '#ajax' => [
        'callback' => '::ajaxEmailValidate',
        'event' => 'input',
        'progress' => [
          'type' => 'none',
        ],
      ],
      '#attributes'  => [
        'autocomplete' => 'off',
      ],
    ];

    $form['user_phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Your phone number:'),
      '#required' => TRUE,
      '#placeholder' => $this->t('+XXXXXXXXXXXX'),
      '#suffix' => '<div class="user__phone-validation-message"></div>',
      '#ajax' => [
        'callback' => '::ajaxPhoneValidate',
        'event' => 'input',
        'progress' => [
          'type' => 'none',
        ],
      ],
      '#attributes'  => [
        'autocomplete' => 'off',
      ],
    ];

    $form['user_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Your message:'),
      '#required' => TRUE,
      '#attributes'  => [
        'autocomplete' => 'off',
      ],
    ];

    $form['user_avatar'] = [
      '#type' => 'managed_file',
      '#title' => t('Your avatar'),
      '#upload_location' => 'public://guest_book/avatars/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg'],
        'file_validate_size' => [2097152],
      ],
    ];

    $form['user_image'] = [
      '#type' => 'managed_file',
      '#title' => t('Your message image'),
      '#upload_location' => 'public://guest_book/images/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg'],
        'file_validate_size' => [5242880],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
      '#ajax' => [
        'callback' => '::ajaxSubmitForm',
        'event' => 'click',
        'progress' => [
          'type' => 'none',
        ],
      ],
    ];

    return $form;
  }

  public function ajaxUserNameValidate(array $form, FormStateInterface $form_state) {
    $userName = $form_state->getValue('user_name');
    $response = new AjaxResponse();
    if (strlen($userName) < 2 || strlen($userName) > 100) {
      $response->addCommand(
        new MessageCommand($this->t('Sorry, the name you entered is incorrect, please enter a valid name.'), '.user__name-validation-message', ['type' => 'error'], TRUE)
      );
    }
    else {
      $response->addCommand(new HtmlCommand('.user__name-validation-message', ''));
    }
    return $response;
  }

  public function ajaxEmailValidate(array $form, FormStateInterface $form_state): AjaxResponse {
    $userEmail = $form_state->getValue('user_email');
    $response  = new AjaxResponse();
    if (!preg_match('/^[_A-Za-z0-9-\\+]*@[A-Za-z0-9-]+(\\.[A-Za-z0-9]+)*(\\.[A-Za-z]{2,})$/', $userEmail)) {
      $response->addCommand(
        new MessageCommand(
          $this->t('Sorry, the email you entered is incorrect, please enter a valid email.'),
          '.user__email-validation-message', ['type' => 'error'], TRUE)
      );
    }
    else {
      $response->addCommand(
        new HtmlCommand(
          '.user__email-validation-message',
          ''
        )
      );
    }
    return $response;
  }

  public function ajaxPhoneValidate(array $form, FormStateInterface $form_state) {
    $userPhone = $form_state->getValue('user_phone');
    $response = new AjaxResponse();
    if (!preg_match('/^((\\+)|(00))[0-9]{12}$/', $userPhone)) {
      $response->addCommand(new MessageCommand(
        $this->t('Sorry, the phone you entered is incorrect, please enter a valid phone number.'),
        '.user__phone-validation-message', ['type' => 'error'], TRUE));
    }
    else {
      $response->addCommand(new HtmlCommand(
          '.user__phone-validation-message',
          '',
        )
      );
    }
    return $response;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  public function ajaxSubmitForm(array &$form, FormStateInterface $form_state):object {
    \Drupal::messenger()->deleteAll();
    $response = new AjaxResponse();
    if (!$form_state->hasAnyErrors())  {
      $response->addCommand(new MessageCommand($this->t('Your comment has been added, thank you.')));
    }
    else {
      $response->addCommand(new MessageCommand(
        $this->t('The information you entered is incorrect, no message has been sent.'), Null, ['type'=>'error']));
    }

    return $response;
  }

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $userAvatar = $form_state->getValue('user_avatar');
    $userImage = $form_state->getValue('user_image');

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

    $data = [
      'user_name' => $form_state->getValue('user_name'),
      'user_email' => $form_state->getValue('user_email'),
      'user_phone' => $form_state->getValue('user_phone'),
      'user_message' => $form_state->getValue('user_message'),
      'user_avatar' => $userAvatar[0],
      'user_image' => $userImage[0],
      'date'      => \Drupal::time()->getCurrentTime(),
    ];

    // Insert data to database.
    \Drupal::database()->insert('guest_book')->fields($data)->execute();

  }

}
